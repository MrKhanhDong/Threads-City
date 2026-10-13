<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->mockGoogle('new-google@example.com');
        $this->get(route('google.callback'))->assertRedirect(route('home'));
        $user = User::sole();
        $this->assertSame('reader', $user->role);
        $this->assertSame('google-123', $user->google_id);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
        $this->assertArrayNotHasKey('google_id', $user->toArray());
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

    public function test_unverified_local_account_is_not_auto_linked(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'unverified@example.com']);
        $this->mockGoogle($user->email);
        $this->get(route('google.callback'))->assertRedirect(route('login'))->assertSessionHasErrors('google');
        $this->assertGuest();
        $this->assertNull($user->fresh()->google_id);
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
