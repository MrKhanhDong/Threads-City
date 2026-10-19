<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class GooglePasswordSetupTest extends TestCase
{
    use RefreshDatabase;

    private function googleReader(): User
    {
        return User::factory()->create(['google_id' => 'google-'.Str::uuid(), 'password' => Str::random(64)]);
    }

    private function sentResetToken(User $user): string
    {
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
            $token = $notification->token;

            return true;
        });
        $this->assertIsString($token);

        return $token;
    }

    public function test_new_google_reader_can_add_password_once_without_email_or_current_password(): void
    {
        Notification::fake();
        $user = User::factory()->create(['google_id' => 'new-google', 'has_local_password' => false, 'password' => Str::random(64)]);
        $this->actingAs($user)->get(route('account.edit'))->assertOk()
            ->assertSee('Thêm mật khẩu')->assertDontSee('name="current_password"', false)
            ->assertDontSee('Gửi liên kết thiết lập mật khẩu');
        $this->from(route('account.edit'))->put(route('account.password'), [
            'password' => 'WebsitePassword123', 'password_confirmation' => 'WebsitePassword123',
            'role' => 'admin', 'email' => 'changed@example.com',
        ])->assertRedirect(route('account.edit'))->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertTrue($user->has_local_password);
        $this->assertTrue(Hash::check('WebsitePassword123', $user->password));
        $this->assertSame('reader', $user->role);
        $this->assertSame('new-google', $user->google_id);
        $this->assertNotSame('changed@example.com', $user->email);
        $this->get(route('account.edit'))->assertOk()->assertSee('name="current_password"', false);
        $this->put(route('account.password'), [
            'password' => 'AnotherPassword123', 'password_confirmation' => 'AnotherPassword123', 'has_local_password' => false,
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('WebsitePassword123', $user->fresh()->password));
        Notification::assertNothingSent();
        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'WebsitePassword123'])
            ->assertRedirect(route('home'))->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_first_password_requires_strength_and_confirmation(): void
    {
        $user = User::factory()->create(['google_id' => 'new-google', 'has_local_password' => false]);
        $hash = $user->password;
        $this->actingAs($user);
        foreach ([['weak', 'weak'], ['Password123', 'Different123']] as [$password, $confirmation]) {
            $this->put(route('account.password'), ['password' => $password, 'password_confirmation' => $confirmation])
                ->assertSessionHasErrors('password');
            $this->assertSame($hash, $user->fresh()->password);
            $this->assertFalse($user->fresh()->has_local_password);
        }
    }

    public function test_google_profile_offers_email_setup_without_requiring_current_password(): void
    {
        $user = $this->googleReader();
        $this->actingAs($user)->get(route('account.edit'))->assertOk()
            ->assertSee('Thiết lập mật khẩu qua email')
            ->assertSee('Gửi liên kết thiết lập mật khẩu')
            ->assertSee('Đã có mật khẩu? Đổi mật khẩu hiện tại')
            ->assertSee('action="'.route('password.email').'"', false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee($user->password, false);
    }

    public function test_local_profile_keeps_normal_password_form(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('account.edit'))->assertOk()
            ->assertSee('Đổi mật khẩu')
            ->assertSee('name="current_password"', false)
            ->assertDontSee('Gửi liên kết thiết lập mật khẩu');
    }

    public function test_setup_sends_only_to_authenticated_email_and_does_not_change_password(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $user = $this->googleReader();
        $other = User::factory()->create();
        $hash = $user->password;
        $this->actingAs($user)->from(route('account.edit'))->post(route('password.email'), [
            'email' => $other->email, 'password' => 'InjectedPassword123', 'role' => 'admin',
        ])->assertRedirect(route('account.edit'))->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        Notification::assertSentTo($user, ResetPassword::class);
        Notification::assertNotSentTo($other, ResetPassword::class);
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame('reader', $user->fresh()->role);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $other->email]);
        $storedToken = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
        $token = $this->sentResetToken($user);
        $this->assertNotSame($token, $storedToken);
        $this->assertTrue(Hash::check($token, $storedToken));
    }

    public function test_email_link_can_be_opened_while_signed_in_and_enables_both_login_methods(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $user = $this->googleReader();
        $googleId = $user->google_id;
        $post = Post::factory()->published()->create();
        $user->bookmarks()->attach($post->id, ['created_at' => now()]);
        $this->actingAs($user)->from(route('account.edit'))->post(route('password.email'))
            ->assertRedirect(route('account.edit'))->assertSessionHasNoErrors();
        $token = $this->sentResetToken($user);
        $this->assertAuthenticatedAs($user);
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()->assertSee('name="password"', false)->assertDontSee('name="current_password"', false);
        $data = ['email' => $user->email, 'token' => $token,
            'password' => 'WebsitePassword123', 'password_confirmation' => 'WebsitePassword123'];
        $this->post(route('password.update'), $data)->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $this->assertGuest();
        $user->refresh();
        $this->assertTrue(Hash::check('WebsitePassword123', $user->password));
        $this->assertSame($googleId, $user->google_id);
        $this->assertSame('reader', $user->role);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('bookmarks', ['user_id' => $user->id, 'post_id' => $post->id]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'WebsitePassword123'])
            ->assertRedirect(route('home'))->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'));
        $profile = (new GoogleUser)->map(['id' => $googleId, 'name' => $user->name, 'email' => $user->email])
            ->setRaw(['email_verified' => true]);
        config(['services.google.client_id' => 'test-only-id', 'services.google.client_secret' => 'test-only-secret']);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn($profile);
        $this->get(route('google.callback'))->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_google_reader_with_known_password_can_still_use_current_password_form(): void
    {
        $user = User::factory()->create(['google_id' => 'already-linked']);
        $this->actingAs($user)->put(route('account.password'), [
            'current_password' => 'password', 'password' => 'ChangedPassword123',
            'password_confirmation' => 'ChangedPassword123',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('ChangedPassword123', $user->fresh()->password));
        $this->assertSame('already-linked', $user->fresh()->google_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_missing_mail_configuration_does_not_send_or_create_reset_tokens(): void
    {
        Notification::fake();
        $user = $this->googleReader();
        $hash = $user->password;
        $this->actingAs($user);
        foreach (['array', 'log'] as $mailer) {
            config(['mail.default' => $mailer]);
            $this->from(route('account.edit'))->post(route('password.email'))
                ->assertRedirect(route('account.edit'))->assertSessionHasErrors('email')
                ->assertSessionMissing('success');
        }
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertAuthenticatedAs($user);
    }

    public function test_smtp_failure_returns_safe_error_and_keeps_account_unchanged(): void
    {
        config(['mail.default' => 'smtp']);
        $user = $this->googleReader();
        $hash = $user->password;
        Password::shouldReceive('sendResetLink')->once()->with(['email' => $user->email])
            ->andThrow(new \RuntimeException('test-only-private-credential'));
        $this->actingAs($user)->from(route('account.edit'))->post(route('password.email'))
            ->assertRedirect(route('account.edit'))->assertSessionHasErrors('email')
            ->assertSessionMissing('success');
        $this->get(route('account.edit'))->assertOk()->assertDontSee('test-only-private-credential');
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertAuthenticatedAs($user);
    }

    public function test_repeated_setup_request_is_throttled_without_false_success(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $user = $this->googleReader();
        $this->actingAs($user)->from(route('account.edit'))->post(route('password.email'))
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->get(route('account.edit'));
        $this->post(route('password.email'))->assertSessionHasErrors('email')
            ->assertSessionMissing('success');
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_wrong_or_expired_token_cannot_set_password_or_log_user_out(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $user = $this->googleReader();
        $hash = $user->password;
        $this->actingAs($user)->post(route('password.email'))->assertSessionHasNoErrors();
        $token = $this->sentResetToken($user);
        $data = ['email' => $user->email, 'token' => 'invalid-token',
            'password' => 'WebsitePassword123', 'password_confirmation' => 'WebsitePassword123'];
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertAuthenticatedAs($user);
        $this->travel(61)->minutes();
        $data['token'] = $token;
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertAuthenticatedAs($user);
    }

    public function test_setup_token_is_bound_to_its_email_and_password_strength_is_enforced(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $user = $this->googleReader();
        $other = User::factory()->create();
        $hash = $user->password;
        $otherHash = $other->password;
        $this->actingAs($user)->post(route('password.email'))->assertSessionHasNoErrors();
        $token = $this->sentResetToken($user);
        $data = ['email' => $other->email, 'token' => $token,
            'password' => 'WebsitePassword123', 'password_confirmation' => 'WebsitePassword123'];
        $this->post(route('password.update'), $data)->assertSessionHasErrors('email');
        $data['email'] = $user->email;
        $data['password'] = $data['password_confirmation'] = 'weak';
        $this->post(route('password.update'), $data)->assertSessionHasErrors('password');
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame($otherHash, $other->fresh()->password);
        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_locked_reader_cannot_request_setup_email(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $user = $this->googleReader();
        $user->forceFill(['is_locked' => true])->save();
        $this->actingAs($user)->post(route('password.email'))->assertRedirect(route('login'));
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertGuest();
    }

    public function test_setup_request_still_requires_csrf(): void
    {
        config(['mail.default' => 'smtp']);
        Notification::fake();
        $user = $this->googleReader();
        $this->actingAs($user);
        $this->app->instance('env', 'local');
        $this->post(route('password.email'))->assertStatus(419);
        $this->app->instance('env', 'testing');
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }
}
