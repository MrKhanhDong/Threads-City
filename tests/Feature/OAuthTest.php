<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class OAuthTest extends TestCase
{
    use RefreshDatabase;

    private function mockGoogle(string $email, string $id = 'google-123', bool $verified = true): void
    {
        config(['services.google.client_id' => 'test-only-id', 'services.google.client_secret' => 'test-only-secret']);
        $profile = (new GoogleUser)->map(['id' => $id, 'name' => 'Google Reader', 'email' => $email])
            ->setRaw(['email_verified' => $verified]);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andReturn($profile);
    }

    public function test_google_creates_verified_reader_without_storing_external_tokens(): void
    {
        Notification::fake();
        $this->mockGoogle('new-google@example.com');
        $this->get(route('google.callback'))->assertRedirect(route('google.profile'));
        $user = User::sole();
        $this->assertSame('reader', $user->role);
        $this->assertSame('google-123', $user->google_id);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
        $this->assertArrayNotHasKey('google_id', $user->toArray());
        $this->assertTrue($user->google_profile_pending);
        $this->assertFalse($user->has_local_password);
        Notification::assertNothingSent();
        $this->get(route('google.profile'))->assertOk()->assertSee('value="Google Reader"', false);
        $this->post(route('google.profile.store'), ['name' => 'Tên tôi chọn', 'role' => 'admin', 'email' => 'other@example.com'])
            ->assertRedirect(route('home'))->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertSame('Tên tôi chọn', $user->name);
        $this->assertFalse($user->google_profile_pending);
        $this->assertSame('reader', $user->role);
        $this->assertSame('new-google@example.com', $user->email);
        $this->get(route('account.edit'))->assertOk()->assertSee('Thêm mật khẩu')->assertDontSee('name="current_password"', false);
    }

    public function test_google_links_only_existing_verified_reader(): void
    {
        $user = User::factory()->create(['email' => 'existing@example.com']);
        $this->mockGoogle($user->email);
        $this->get(route('google.callback'))->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->fresh()->google_id);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_google_verifies_existing_email_and_revokes_unverified_credentials(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'unverified@example.com']);
        Password::createToken($user);
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->mockGoogle($user->email);
        $this->get(route('google.callback'))->assertRedirect(route('google.profile'))->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $user->refresh();
        $this->assertSame('google-123', $user->google_id);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertFalse($user->has_local_password);
        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_pending_profile_cannot_be_skipped_and_can_keep_google_name(): void
    {
        $user = User::factory()->create(['google_id' => 'google-123', 'google_profile_pending' => true, 'name' => 'Google Reader']);
        $this->actingAs($user)->get(route('home'))->assertRedirect(route('google.profile'));
        $this->put(route('account.password'), [])->assertRedirect(route('google.profile'));
        foreach (['', '   ', str_repeat('a', 101), ['invalid']] as $name) {
            $this->from(route('google.profile'))->post(route('google.profile.store'), ['name' => $name])->assertSessionHasErrors('name');
            $this->assertTrue($user->fresh()->google_profile_pending);
        }
        $this->post(route('google.profile.store'), ['name' => 'Google Reader'])->assertRedirect(route('home'));
        $this->assertFalse($user->fresh()->google_profile_pending);
        $this->assertSame('Google Reader', $user->fresh()->name);
        $this->post(route('logout'));
        $this->mockGoogle($user->email);
        $this->get(route('google.callback'))->assertRedirect(route('home'));
        $this->get(route('google.profile'))->assertRedirect(route('home'));
    }

    public function test_pending_profile_resumes_after_signing_in_again(): void
    {
        $user = User::factory()->create(['google_id' => 'google-123', 'google_profile_pending' => true]);
        $this->mockGoogle($user->email);
        $this->get(route('google.callback'))->assertRedirect(route('google.profile'));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_guest_cannot_complete_a_profile(): void
    {
        $this->get(route('google.profile'))->assertRedirect(route('login'));
        $this->post(route('google.profile.store'), ['name' => 'Visitor'])->assertRedirect(route('login'));
    }

    public function test_conflicting_google_identity_cannot_link_by_email(): void
    {
        $user = User::factory()->create(['google_id' => 'other-google']);
        $this->mockGoogle($user->email);
        $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertSame('other-google', $user->fresh()->google_id);
    }

    public function test_admin_cannot_sign_in_or_gain_google_link(): void
    {
        $user = User::factory()->create(['email' => 'author@example.com', 'role' => 'admin']);
        $this->mockGoogle($user->email);
        $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
    }

    public function test_locked_google_reader_cannot_sign_in(): void
    {
        $user = User::factory()->create(['email' => 'locked@example.com', 'google_id' => 'google-123', 'is_locked' => true]);
        $this->mockGoogle($user->email);
        $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_unverified_provider_email_is_rejected(): void
    {
        $this->mockGoogle('external@example.com', verified: false);
        $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('google');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_invalid_oauth_state_fails_without_logging_in(): void
    {
        config(['services.google.client_id' => 'test-only-id']);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->once()->andThrow(new InvalidStateException);
        $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_missing_credentials_offer_email_login(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);
        $this->get(route('google.redirect'))->assertRedirect(route('login'))->assertSessionHasErrors('google');
        $this->get(route('google.callback', ['error' => 'access_denied']))->assertRedirect(route('login'))->assertSessionHasErrors('google');
        $this->assertGuest();
    }
}
