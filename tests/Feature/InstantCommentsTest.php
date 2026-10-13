<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InstantCommentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_comment_is_immediately_visible_to_guest_and_cannot_spoof_status_or_author(): void
    {
        $post = Post::factory()->published()->create();
        $reader = User::factory()->create();
        $this->actingAs($reader)->from(route('posts.show', $post->slug))
            ->post(route('comments.store', $post->id), ['body' => 'Bình luận hiển thị ngay', 'status' => 'hidden', 'user_id' => $post->user_id])
            ->assertRedirect(route('posts.show', $post->slug).'#comment-'.Comment::sole()->id)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('comments', ['user_id' => $reader->id, 'status' => 'approved', 'parent_id' => null]);
        auth()->logout();
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee('Bình luận hiển thị ngay')->assertDontSee('được duyệt trước');
    }

    public function test_replies_are_public_and_loaded_on_demand_in_one_flat_thread(): void
    {
        $post = Post::factory()->published()->create();
        $author = User::factory()->create(['name' => 'Người được trả lời']);
        $root = Comment::factory()->create(['post_id' => $post->id, 'user_id' => $author->id, 'body' => 'Lời nhắn gốc']);
        $this->actingAs(User::factory()->create());
        $this->post(route('comments.store', $post->id), ['parent_id' => $root->id, 'body' => 'Trả lời cấp một'])->assertSessionHasNoErrors();
        $first = Comment::where('body', 'Trả lời cấp một')->sole();
        $this->post(route('comments.store', $post->id), ['parent_id' => $first->id, 'body' => 'Trả lời cấp hai'])->assertSessionHasNoErrors();
        $second = Comment::where('body', 'Trả lời cấp hai')->sole();
        $this->assertSame('approved', $first->status);
        $this->assertSame('approved', $second->status);
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee('name="parent_id" value="'.$root->id.'"', false)
            ->assertSee('Xem trả lời')->assertDontSee('Trả lời cấp một');
        $data = $this->getJson(route('comments.replies', $root))->assertOk()->json();
        $this->assertStringContainsString('Trả lời Người được trả lời', strip_tags($data['html']));
        $this->assertStringContainsString('reply-context', $data['html']);
        auth()->logout();
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee('Lời nhắn gốc')->assertDontSee('Trả lời cấp hai');
        $html = $this->getJson(route('comments.replies', $root))->assertOk()->json('html');
        $this->assertStringContainsString('Trả lời cấp một', $html);
        $this->assertStringContainsString('Trả lời cấp hai', $html);
        $this->assertStringContainsString('href="#comment-'.$root->id.'"', $html);
        $this->assertStringContainsString('Đăng nhập để trả lời', $html);
    }

    public function test_reader_edit_keeps_public_comment_public(): void
    {
        $reader = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $reader->id]);
        $this->actingAs($reader)->patch(route('comments.update', $comment), ['body' => 'Nội dung mới hiển thị ngay', 'status' => 'pending'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $comment->fresh()->status);
        auth()->logout();
        $this->get(route('posts.show', $comment->post->slug))->assertOk()->assertSee('Nội dung mới hiển thị ngay');
    }

    public function test_editing_moderated_or_legacy_pending_comment_does_not_republish_it(): void
    {
        $reader = User::factory()->create();
        $this->actingAs($reader);
        foreach (['hidden', 'spam', 'pending'] as $status) {
            $comment = Comment::factory()->create(['user_id' => $reader->id, 'status' => $status]);
            $this->patch(route('comments.update', $comment), ['body' => 'Sửa nhưng không tự công khai', 'status' => 'approved'])->assertSessionHasNoErrors();
            $this->assertSame($status, $comment->fresh()->status);
        }
    }

    public function test_reply_cannot_target_hidden_ancestor_or_another_posts_comment(): void
    {
        $post = Post::factory()->published()->create();
        $root = Comment::factory()->create(['post_id' => $post->id, 'status' => 'hidden']);
        $child = Comment::factory()->create(['post_id' => $post->id, 'parent_id' => $root->id]);
        $foreign = Comment::factory()->create();
        $this->actingAs(User::factory()->create());
        foreach ([$root->id, $child->id, $foreign->id] as $parentId) {
            $this->post(route('comments.store', $post->id), ['body' => 'Trả lời sai', 'parent_id' => $parentId])->assertNotFound();
        }
        $this->assertDatabaseMissing('comments', ['body' => 'Trả lời sai']);
    }

    public function test_database_default_is_public_but_pending_legacy_records_are_retained(): void
    {
        $post = Post::factory()->published()->create();
        $reader = User::factory()->create();
        DB::table('comments')->insert(['post_id' => $post->id, 'user_id' => $reader->id, 'body' => 'Theo mặc định database', 'created_at' => now(), 'updated_at' => now()]);
        $this->assertDatabaseHas('comments', ['body' => 'Theo mặc định database', 'status' => 'approved']);
        $legacy = Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending', 'body' => 'Nội dung cũ chưa được phép công khai']);
        $this->assertSame('pending', $legacy->fresh()->status);
        $this->get(route('posts.show', $post->slug))->assertOk()->assertDontSee($legacy->body);
    }

    public function test_reply_validation_keeps_text_only_in_the_matching_reply_form(): void
    {
        $post = Post::factory()->published()->create();
        $root = Comment::factory()->create(['post_id' => $post->id]);
        $other = Comment::factory()->create(['post_id' => $post->id]);
        $body = '<script>unsafe()</script>'.str_repeat('a', 5001);
        $this->actingAs(User::factory()->create())->from(route('posts.show', $post->slug))
            ->post(route('comments.store', $post->id), ['parent_id' => $root->id, 'body' => $body])
            ->assertSessionHasErrors('body')->assertSessionHasInput('body', $body);
        $response = $this->get(route('posts.show', $post->slug))->assertOk()->assertDontSee('<script>unsafe()</script>', false);
        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument;
        $document->loadHTML($response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $this->assertSame('', $document->getElementById('body')->textContent);
        $this->assertSame('', $document->getElementById('reply-'.$other->id)->textContent);
        $this->assertSame($body, $document->getElementById('reply-'.$root->id)->textContent);
        $this->assertDatabaseCount('comments', 2);
    }

    public function test_admin_can_hide_and_restore_public_comment_without_enabling_new_pending_states(): void
    {
        $comment = Comment::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->patch(route('admin.comments.update', $comment), ['status' => 'hidden'])->assertRedirect();
        $this->get(route('posts.show', $comment->post->slug))->assertDontSee($comment->body);
        $this->patch(route('admin.comments.update', $comment), ['status' => 'approved'])->assertRedirect();
        $this->get(route('posts.show', $comment->post->slug))->assertSee($comment->body);
        $this->patch(route('admin.comments.update', $comment), ['status' => 'pending'])->assertSessionHasErrors('status');
    }
}
