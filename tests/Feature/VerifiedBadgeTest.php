<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifiedBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_grant_and_revoke_badges_without_changing_permissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reader = User::factory()->create();

        $this->actingAs($admin)->from(route('admin.users.index'))
            ->patch(route('admin.users.verification', $reader), ['is_verified' => 1, 'role' => 'admin', 'is_locked' => 1])
            ->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();

        $this->assertTrue($reader->fresh()->is_verified);
        $this->assertSame('reader', $reader->fresh()->role);
        $this->assertFalse($reader->fresh()->is_locked);

        $this->patch(route('admin.users.verification', $reader), ['is_verified' => 0])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($reader->fresh()->is_verified);
    }

    public function test_admin_can_manage_badges_for_admin_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch(route('admin.users.verification', $admin), ['is_verified' => true])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($admin->fresh()->is_verified);

        $this->patch(route('admin.users.verification', $admin), ['is_verified' => false])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($admin->fresh()->is_verified);
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_guests_and_verified_members_cannot_grant_or_revoke_badges(): void
    {
        $reader = User::factory()->create(['is_verified' => true]);
        $target = User::factory()->create();

        $this->patch(route('admin.users.verification', $target), ['is_verified' => true])
            ->assertRedirect(route('login'));

        $this->actingAs($reader)->patch(route('admin.users.verification', $target), ['is_verified' => true])
            ->assertForbidden();
        $this->patch(route('admin.users.verification', $reader), ['is_verified' => false])
            ->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();

        $this->assertFalse($target->fresh()->is_verified);
        $this->assertTrue($reader->fresh()->is_verified);
    }

    public function test_locked_admin_cannot_manage_badges(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_locked' => true]);
        $reader = User::factory()->create();

        $this->actingAs($admin)->patchJson(route('admin.users.verification', $reader), ['is_verified' => true])
            ->assertForbidden();

        $this->assertFalse($reader->fresh()->is_verified);
    }

    public function test_badge_updates_require_an_explicit_boolean(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reader = User::factory()->create();
        $this->actingAs($admin);

        foreach ([[], ['is_verified' => 'yes'], ['is_verified' => 2], ['is_verified' => null]] as $payload) {
            $this->patch(route('admin.users.verification', $reader), $payload)
                ->assertSessionHasErrors('is_verified');
            $this->assertFalse($reader->fresh()->is_verified);
        }
    }

    public function test_members_cannot_self_verify_through_profile_or_mass_assignment(): void
    {
        $reader = User::factory()->create();

        $this->actingAs($reader)->patch(route('account.update'), ['name' => 'Tên mới', 'is_verified' => true])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($reader->fresh()->is_verified);

        $reader->fill(['is_verified' => true])->save();
        $this->assertFalse($reader->fresh()->is_verified);
    }

    public function test_registration_cannot_grant_a_badge(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Thành viên mới', 'email' => 'member@example.com',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
            'is_verified' => true, 'role' => 'admin',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $reader = User::sole();
        $this->assertFalse($reader->is_verified);
        $this->assertSame('reader', $reader->role);
    }

    public function test_admin_user_list_includes_admins_and_supports_username_search(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'username' => 'site_owner']);
        $reader = User::factory()->create(['username' => 'vip_member']);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()->assertSee($admin->email)->assertSee($reader->email)
            ->assertSee('Cấp tích xanh')->assertSee(route('admin.users.verification', $reader), false);

        $this->get(route('admin.users.index', ['q' => 'vip_member']))
            ->assertOk()->assertSee($reader->email)->assertDontSee($admin->email);
    }

    public function test_granted_badges_appear_on_feed_detail_profile_and_search_and_disappear_when_revoked(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $author = User::factory()->create(['username' => 'verified_person']);
        $post = Post::factory()->published()->create(['user_id' => $author->id, 'slug' => 'thread-verified-demo']);
        $urls = [
            route('home'),
            route('threads.show', $post),
            route('profile.show', $author->username),
            route('search.index', ['q' => $author->username, 'type' => 'people']),
        ];

        $this->actingAs($admin)->patch(route('admin.users.verification', $author), ['is_verified' => true])
            ->assertSessionHasNoErrors();

        foreach ($urls as $url) {
            $this->get($url)->assertOk()->assertSee('aria-label="Tài khoản đã xác minh"', false);
        }

        $this->patch(route('admin.users.verification', $author), ['is_verified' => false])
            ->assertSessionHasNoErrors();

        foreach ($urls as $url) {
            $this->get($url)->assertOk()->assertDontSee('aria-label="Tài khoản đã xác minh"', false);
        }
    }

    public function test_email_verification_alone_does_not_display_an_identity_badge(): void
    {
        $reader = User::factory()->create(['username' => 'regular_member']);
        Post::factory()->published()->create(['user_id' => $reader->id, 'slug' => 'thread-unverified-demo']);

        $this->assertTrue($reader->hasVerifiedEmail());
        $this->get(route('home'))->assertOk()->assertDontSee('aria-label="Tài khoản đã xác minh"', false);
        $this->get(route('profile.show', $reader->username))->assertOk()
            ->assertDontSee('aria-label="Tài khoản đã xác minh"', false);
    }
}
