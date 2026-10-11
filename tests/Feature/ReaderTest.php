<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Tests\TestCase;

class ReaderTest extends TestCase
{
    public function test_guest_cannot_save_comment_or_view_saved_list(): void
    {
        $post=$this->published();
        $this->get(route('saved'))->assertRedirect(route('login'));
        $this->post(route('bookmarks.store',$post))->assertRedirect(route('login'));
        $this->post(route('comments.store',$post),['body'=>'A comment'])->assertRedirect(route('login'));
    }
    public function test_bookmarks_persist_per_account_and_are_idempotent(): void
    {
        $reader=$this->reader(); $post=$this->published();
        $reader->bookmarks()->detach();
        $this->actingAs($reader)->post(route('bookmarks.store',$post))->assertRedirect();
        $this->post(route('bookmarks.store',$post))->assertRedirect();
        $this->assertSame(1,$reader->bookmarks()->count());
        $this->get(route('saved'))->assertOk()->assertViewHas('posts',fn($p)=>$p->total()===1)->assertSee($post->title);
        $this->actingAs($this->admin())->get(route('saved'))->assertOk()->assertViewHas('posts',fn($p)=>$p->total()===0);
        $this->actingAs($reader)->delete(route('bookmarks.destroy',$post))->assertRedirect();
        $this->assertSame(0,$reader->bookmarks()->count());
    }
    public function test_unpublished_posts_cannot_be_saved_or_commented_and_saved_archive_disappears(): void
    {
        $draft=Post::where('status','draft')->firstOrFail();
        $this->actingAs($this->reader())->post(route('bookmarks.store',$draft))->assertNotFound();
        $this->post(route('comments.store',$draft),['body'=>'Should never publish'])->assertNotFound();
        $post=$this->published(); $this->post(route('bookmarks.store',$post))->assertRedirect();
        $post->update(['status'=>'archived']);
        $this->get(route('saved'))->assertOk()->assertViewHas('posts',fn($p)=>!$p->contains('id',$post->id));
    }
    public function test_reader_comment_is_pending_private_until_approved_and_escaped(): void
    {
        $post=$this->published();
        $body='<script>commentAttack()</script>';
        $this->actingAs($this->reader())->post(route('comments.store',$post),['body'=>$body,'status'=>'approved','user_id'=>$this->admin()->id])->assertRedirect();
        $comment=Comment::where('body',$body)->firstOrFail();
        $this->assertSame('pending',$comment->status);
        $this->assertSame($this->reader()->id,$comment->user_id);
        $this->get(route('posts.show',$post->slug))->assertSee('&lt;script&gt;commentAttack()',false)->assertDontSee('<script>commentAttack()',false);
        $this->app['auth']->forgetGuards();
        $this->get(route('posts.show',$post->slug))->assertDontSee('commentAttack');
        $this->actingAs($this->admin())->patch(route('admin.comments.update',$comment),['status'=>'approved'])->assertRedirect();
        $this->app['auth']->forgetGuards();
        $this->get(route('posts.show',$post->slug))->assertSee('&lt;script&gt;commentAttack()',false);
    }
    public function test_comment_validation_and_other_readers_cannot_delete_it(): void
    {
        $post=$this->published(); $reader=$this->reader();
        $this->actingAs($reader)->postJson(route('comments.store',$post),['body'=>'x'])->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->postJson(route('comments.store',$post),['body'=>str_repeat('x',2001)])->assertUnprocessable();
        $comment=Comment::create(['post_id'=>$post->id,'user_id'=>$reader->id,'body'=>'Own comment','status'=>'pending']);
        $other=User::create(['name'=>'Other','email'=>'other@blog.local','password'=>'Password12345','role'=>'reader']);
        $this->actingAs($other)->delete(route('comments.destroy',$comment))->assertForbidden();
        $this->actingAs($reader)->delete(route('comments.destroy',$comment))->assertRedirect();
        $this->assertDatabaseMissing('comments',['id'=>$comment->id]);
    }
}
