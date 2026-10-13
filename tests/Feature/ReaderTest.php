<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_reader_comment_is_published_immediately_and_escaped(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        $body = '<script>alert("xss")</script> Lời nhắn';
        $this->actingAs($user)->post(route('comments.store', $post->id), ['body' => $body, 'status' => 'approved', 'user_id' => 999])
            ->assertRedirect()->assertSessionHasNoErrors();
        $comment = Comment::sole();
        $this->assertSame('approved', $comment->status);
        $this->assertSame($user->id, $comment->user_id);
        $this->get(route('posts.show', $post->slug))->assertOk()->assertSee($body)->assertDontSee($body, false);
    }

    public function test_guest_and_unverified_reader_cannot_comment(): void
    {
        $post = Post::factory()->published()->create();
        $this->post(route('comments.store', $post->id), ['body' => 'Lời nhắn'])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->unverified()->create())
            ->post(route('comments.store', $post->id), ['body' => 'Lời nhắn'])->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_reply_requires_approved_parent_on_same_post_and_has_depth_limit(): void
    {
        $post = Post::factory()->published()->create();
        $root = Comment::factory()->approved()->create(['post_id' => $post->id]);
        $first = Comment::factory()->approved()->create(['post_id' => $post->id, 'parent_id' => $root->id]);
        $second = Comment::factory()->approved()->create(['post_id' => $post->id, 'parent_id' => $first->id]);
        $pending = Comment::factory()->create(['post_id' => $post->id, 'status' => 'hidden']);
        $foreign = Comment::factory()->approved()->create();
        $this->actingAs(User::factory()->create());
        $this->post(route('comments.store', $post->id), ['body' => 'Trả lời hợp lệ', 'parent_id' => $root->id])->assertSessionHasNoErrors();
        $this->post(route('comments.store', $post->id), ['body' => 'Trả lời quá sâu', 'parent_id' => $second->id])->assertSessionHasErrors('parent_id');
        $this->post(route('comments.store', $post->id), ['body' => 'Sai bài viết', 'parent_id' => $foreign->id])->assertNotFound();
        $this->post(route('comments.store', $post->id), ['body' => 'Chưa được duyệt', 'parent_id' => $pending->id])->assertNotFound();
        $this->assertDatabaseHas('comments', ['parent_id' => $root->id, 'body' => 'Trả lời hợp lệ', 'status' => 'approved']);
        $this->assertDatabaseMissing('comments', ['body' => 'Trả lời quá sâu']);
    }

    public function test_reader_can_edit_own_comment_but_not_another_readers(): void
    {
        $user = User::factory()->create();
        $own = Comment::factory()->approved()->create(['user_id' => $user->id]);
        $other = Comment::factory()->approved()->create();
        $this->actingAs($user)->patch(route('comments.update', $own), ['body' => 'Nội dung đã sửa'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('approved', $own->fresh()->status);
        $this->patch(route('comments.update', $other), ['body' => 'Sửa trái phép'])->assertForbidden();
        $this->delete(route('comments.destroy', $other))->assertForbidden();
        $this->delete(route('comments.destroy', $own))->assertRedirect();
        $this->assertModelMissing($own);
        $this->assertModelExists($other);
    }

    public function test_report_is_unique_and_cannot_report_hidden_comment(): void
    {
        $comment = Comment::factory()->approved()->create();
        $pending = Comment::factory()->create(['status' => 'hidden']);
        $this->actingAs(User::factory()->create());
        $this->post(route('comments.report', $comment), ['reason' => 'Nội dung không phù hợp'])->assertRedirect();
        $this->post(route('comments.report', $comment), ['reason' => 'Cập nhật nội dung báo cáo'])->assertRedirect();
        $this->assertDatabaseCount('comment_reports', 1);
        $this->assertDatabaseHas('comment_reports', ['comment_id' => $comment->id, 'reason' => 'Cập nhật nội dung báo cáo', 'resolved' => false]);
        $this->post(route('comments.report', $pending), ['reason' => 'Không được báo cáo bản ẩn'])->assertNotFound();
    }

    public function test_bookmark_is_idempotent_and_private_posts_cannot_be_saved(): void
    {
        $post = Post::factory()->published()->create();
        $draft = Post::factory()->create();
        $this->actingAs(User::factory()->create());
        $this->post(route('bookmarks.store', $post->id))->assertRedirect();
        $this->post(route('bookmarks.store', $post->id))->assertRedirect();
        $this->post(route('bookmarks.store', $draft->id))->assertNotFound();
        $this->assertDatabaseCount('bookmarks', 1);
        $this->get(route('saved'))->assertOk()->assertSee($post->title)->assertDontSee($draft->title);
        $this->delete(route('bookmarks.destroy', $post->id))->assertRedirect();
        $this->assertDatabaseCount('bookmarks', 0);
    }

    public function test_profile_uploads_real_avatar_and_ignores_privileged_fields(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->patch(route('account.update'), [
            'name' => 'Tên mới', 'bio' => 'Hồ sơ cá nhân', 'role' => 'admin', 'is_locked' => true,
            'email' => 'changed@example.com', 'avatar' => UploadedFile::fake()->image('avatar.png', 120, 120),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $user->refresh();
        $this->assertSame('reader', $user->role);
        $this->assertFalse($user->is_locked);
        $this->assertNotSame('changed@example.com', $user->email);
        Storage::disk('local')->assertExists($user->avatar_path);
        $this->get(route('avatar.show', $user))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->patch(route('account.update'), ['name' => 'Tên mới', 'avatar' => UploadedFile::fake()->create('bad.svg', 1, 'image/svg+xml')])
            ->assertSessionHasErrors('avatar');
    }

    public function test_password_update_requires_old_password_and_preserves_current_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put(route('account.password'), [
            'current_password' => 'wrong', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
        ])->assertSessionHasErrors('current_password');
        $this->put(route('account.password'), [
            'current_password' => 'password', 'password' => 'NewPassword123', 'password_confirmation' => 'NewPassword123',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertTrue(Hash::check('NewPassword123', $user->fresh()->password));
        $this->get(route('account.edit'))->assertOk();
    }

    public function test_locked_reader_is_logged_out_before_any_write(): void
    {
        $user = User::factory()->create(['is_locked' => true]);
        $post = Post::factory()->published()->create();
        $this->actingAs($user)->post(route('bookmarks.store', $post->id))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseCount('bookmarks', 0);
    }
}
