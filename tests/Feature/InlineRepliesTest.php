<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InlineRepliesTest extends TestCase
{
    use RefreshDatabase;

    public function test_thread_and_comment_have_their_own_inline_reply_targets(): void
    {
        $thread = Post::factory()->published()->create();
        $comment = Post::factory()->published()->create(['parent_id' => $thread->id, 'type' => 'reply']);
        $reader = User::factory()->create();

        $this->actingAs($reader)->get(route('threads.show', $thread))->assertOk()
            ->assertSee('id="inline-reply-template"', false)
            ->assertSee('data-thread-detail-composer', false)
            ->assertSee('data-inline-reply="'.$thread->id.'"', false)
            ->assertSee('data-inline-reply-slot="'.$thread->id.'"', false)
            ->assertSee('data-inline-reply="'.$comment->id.'"', false)
            ->assertSee('data-inline-reply-slot="'.$comment->id.'"', false)
            ->assertDontSee('id="replyForm"', false);
    }

    public function test_reply_to_a_comment_is_saved_under_that_comment(): void
    {
        $thread = Post::factory()->published()->create();
        $comment = Post::factory()->published()->create(['parent_id' => $thread->id, 'type' => 'reply']);
        $reader = User::factory()->create();

        $response = $this->actingAs($reader)->postJson(route('threads.store'), ['parent_id' => $comment->id, 'type' => 'reply', 'content' => 'Đang trả lời đúng bình luận này.'])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseHas('posts', ['id' => $response->json('id'), 'parent_id' => $comment->id, 'user_id' => $reader->id]);
        $this->assertSame(1, $comment->fresh()->replies_count);
    }

    public function test_guests_have_reply_links_but_cannot_submit_comments(): void
    {
        $thread = Post::factory()->published()->create();
        $this->get(route('threads.show', $thread))->assertOk()->assertDontSee('id="inline-reply-template"', false);
        $this->postJson(route('threads.store'), ['parent_id' => $thread->id, 'type' => 'reply', 'content' => 'Guest reply'])->assertUnauthorized();
    }

    public function test_post_cards_open_public_details_with_the_original_post_and_its_comments(): void
    {
        $thread = Post::factory()->published()->create(['content' => 'Bài viết gốc để xem chi tiết']);
        $comment = Post::factory()->published()->create(['parent_id' => $thread->id, 'type' => 'reply', 'content' => 'Bình luận công khai bên dưới bài viết']);

        $this->get(route('home'))->assertOk()
            ->assertSee('data-thread-url="'.route('threads.show', $thread->id).'"', false)
            ->assertSee('href="'.route('threads.show', $thread->id).'#replyBox"', false);
        $this->get(route('threads.show', $thread))->assertOk()
            ->assertSee($thread->content)->assertSee($comment->content)
            ->assertSee('data-reply-home="'.$thread->id.'"', false)
            ->assertSee('Đăng nhập để tham gia bình luận.');
    }
}
