<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhuongInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_home_restores_phuong_shell_theme_columns_and_current_auth_prompt(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('journal-shell', false)->assertSee('journal-sidebar', false)
            ->assertSee('data-column-workspace', false)->assertSee('data-desktop-column-frame', false)
            ->assertSee('data-theme-choice="light"', false)->assertSee('data-theme-choice="dark"', false)
            ->assertSee('data-theme-choice="auto"', false)
            ->assertSee('data-live-region="feed"', false)->assertSee('threadsAuthPromptModal', false)
            ->assertDontSee('threads-app-shell', false);
    }

    public function test_signed_in_home_keeps_current_composer_poll_and_modal_actions(): void
    {
        $reader = User::factory()->create(['username' => 'phuong_reader']);
        $post = Post::factory()->published()->create(['title' => 'Bài đăng vẫn còn', 'content' => 'Bài đăng vẫn còn']);
        $this->actingAs($reader)->get(route('home'))->assertOk()
            ->assertSee('Bài đăng vẫn còn')->assertSee('newThreadModal', false)
            ->assertSee('data-compose="new"', false)->assertSee('sidebarNotificationBadge', false)
            ->assertSee('quotePickerModal', false)->assertSee('locationPickerModal', false)
            ->assertSee('modalAddPollBtn', false)->assertSee('action-like-btn', false)
            ->assertSee('thread-option-', false);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Bài đăng vẫn còn']);
    }

    public function test_search_from_restored_sidebar_filters_real_public_posts(): void
    {
        $match = Post::factory()->published()->create(['title' => 'Tìm giao diện Phuong', 'content' => 'Nội dung cần tìm']);
        Post::factory()->published()->create(['title' => 'Bài khác', 'content' => 'Không trùng']);
        Post::factory()->create(['title' => 'Bản nháp Phuong', 'content' => 'Không công khai']);
        $this->get(route('home', ['search' => 1, 'q' => 'Phuong']))->assertRedirect(route('search.index', ['q' => 'Phuong']));
        $this->get(route('search.index', ['q' => 'Phuong']))->assertOk()
            ->assertSee('discovery-search-form', false)->assertSee('Nội dung cần tìm')
            ->assertDontSee('Bản nháp Phuong')
            ->assertViewHas('threads', fn ($threads): bool => $threads->total() === 1 && $threads->first()->id === $match->id);
        $this->getJson(route('home', ['q' => ['invalid']]))->assertUnprocessable();
    }

    public function test_current_ajax_feed_and_live_protocol_still_work_with_restored_layout(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Bài cập nhật']);
        $json = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->getJson(route('home'))->assertOk();
        $this->assertStringContainsString('thread-'.$post->id, $json->json('html'));
        $this->assertStringNotContainsString('journal-shell', $json->json('html'));
        $this->get(route('home'), ['X-Threads-Live' => '1', 'Accept' => 'text/html'])->assertOk()->assertJsonPath('regions.0.name', 'feed');
    }

    public function test_profile_saved_account_and_activity_pages_render_in_the_restored_shell(): void
    {
        $reader = User::factory()->create(['username' => 'phuong_profile']);
        $this->actingAs($reader);
        foreach ([route('profile.show', $reader->username), route('saved'), route('account.edit'), route('notifications.index')] as $url) {
            $this->get($url)->assertOk()->assertSee('journal-shell', false)->assertSee('data-column-workspace', false);
        }
    }
}
