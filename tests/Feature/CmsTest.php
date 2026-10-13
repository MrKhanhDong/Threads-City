<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_always_creates_unverified_reader(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $this->post(route('register.store'), [
            'name' => 'Độc giả mới', 'email' => 'NEW@Example.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
            'role' => 'admin', 'is_locked' => false, 'email_verified_at' => now()->toDateTimeString(),
        ])->assertRedirect(route('verification.notice'))->assertSessionHasNoErrors();
        $user = User::sole();
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('reader', $user->role);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertTrue(Hash::check('Password123', $user->password));
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get(route('verification.notice'))->assertOk();
    }

    public function test_duplicate_and_weak_password_registration_are_rejected(): void
    {
        User::factory()->create(['email' => 'duplicate@example.com']);
        $this->post(route('register.store'), [
            'name' => 'Trùng email', 'email' => 'DUPLICATE@example.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ])->assertSessionHasErrors('email');
        $this->post(route('register.store'), [
            'name' => 'Mật khẩu yếu', 'email' => 'another@example.com',
            'password' => 'weak', 'password_confirmation' => 'weak',
        ])->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_email_login_and_logout_use_real_password_hash(): void
    {
        $user = User::factory()->create(['email' => 'reader@example.com']);
        $this->post(route('login.store'), ['email' => 'READER@example.com', 'password' => 'password'])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
        $this->get(route('account.edit'))->assertRedirect(route('login'));
    }

    public function test_admin_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('admin.index'));
        $this->get(route('admin.index'))->assertOk();
    }

    public function test_locked_account_and_wrong_password_cannot_login(): void
    {
        $user = User::factory()->create(['is_locked' => true]);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), ['email' => 'rate-limit@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post(route('login.store'), ['email' => 'rate-limit@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_signed_verification_accepts_only_correct_identity_and_signature(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url.'&tamper=1')->assertForbidden();
        $wrong = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), ['id' => $user->id, 'hash' => sha1('wrong@example.com')]);
        $this->get($wrong)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        $this->get($url)->assertRedirect(route('home'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_mail_not_configured_is_explicit_and_never_logs_secret_tokens(): void
    {
        config(['mail.default' => 'array']);
        $user = User::factory()->unverified()->create();
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->actingAs($user)->post(route('verification.send'))->assertSessionHasErrors('email');
    }

    public function test_password_reset_sends_notification_and_consumes_token_once(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect()->assertSessionHasNoErrors();
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });
        $this->assertNotNull($token);
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'ResetPassword123', 'password_confirmation' => 'ResetPassword123'];
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();
        $this->post(route('password.update'), $payload)->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('ResetPassword123', $user->fresh()->password));
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->post(route('password.update'), $payload)->assertSessionHasErrors('email');
    }

    public function test_invalid_password_reset_does_not_change_password(): void
    {
        $user = User::factory()->create();
        $hash = $user->password;
        $this->post(route('password.update'), [
            'email' => $user->email, 'token' => 'invalid-token', 'password' => 'ResetPassword123', 'password_confirmation' => 'ResetPassword123',
        ])->assertSessionHasErrors('email');
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_changed_password_revokes_old_authenticated_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['password_hash_web' => $user->password]);
        $newHash = Hash::make('DifferentPassword123');
        $user->forceFill(['password' => $newHash])->save();
        $this->get(route('account.edit'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_csrf_is_enforced_when_not_in_testing_runtime(): void
    {
        // Keep the guarded test database but enable the real non-testing CSRF middleware.
        $this->app->instance('env', 'local');
        $this->post(route('login.store'), ['email' => 'csrf@example.com', 'password' => 'password'])->assertStatus(419);
        $this->app->instance('env', 'testing');
    }
}
