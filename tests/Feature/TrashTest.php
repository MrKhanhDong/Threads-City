<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostDailyView;
use App\Models\Tag;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrashTest extends TestCase
{
    public function test_trash_page_and_mutations_require_admin(): void
    {
        $post=$this->published();
        $this->get(route('admin.posts.trash'))->assertRedirect(route('login'));
        $this->patch(route('admin.posts.restore',$post->id))->assertRedirect(route('login'));
        $this->delete(route('admin.posts.force-destroy',$post->id),['confirm_delete'=>1])->assertRedirect(route('login'));
        $this->actingAs($this->reader())->get(route('admin.posts.trash'))->assertForbidden();
        $this->patch(route('admin.posts.restore',$post->id))->assertForbidden();
        $this->delete(route('admin.posts.force-destroy',$post->id),['confirm_delete'=>1])->assertForbidden();
        $this->actingAs($this->admin())->get(route('admin.posts.trash'))->assertOk()->assertSee('Thùng rác đang trống');
    }

    public function test_soft_delete_keeps_files_comments_bookmarks_tags_and_daily_stats(): void
    {
        Storage::fake('public');
        $post=$this->published(); $tag=Tag::firstOrFail();
        $post->tags()->syncWithoutDetaching([$tag->id]);
        Storage::disk('public')->put('covers/retained.png','test file');
        $post->update(['cover_path'=>'covers/retained.png']);
        $comment=Comment::create(['post_id'=>$post->id,'user_id'=>$this->reader()->id,'body'=>'Retained on soft delete','status'=>'pending']);
        $this->reader()->bookmarks()->syncWithoutDetaching([$post->id]);
        PostDailyView::create(['post_id'=>$post->id,'view_date'=>now()->toDateString(),'views'=>3]);
        $this->actingAs($this->admin())->delete(route('admin.posts.destroy',$post))->assertRedirect(route('admin.posts.index'));
        $this->assertSoftDeleted('posts',['id'=>$post->id]);
        $this->assertDatabaseHas('comments',['id'=>$comment->id,'body'=>'Retained on soft delete']);
        $this->assertDatabaseHas('bookmarks',['post_id'=>$post->id,'user_id'=>$this->reader()->id]);
        $this->assertDatabaseHas('post_tag',['post_id'=>$post->id,'tag_id'=>$tag->id]);
        $this->assertDatabaseHas('post_daily_views',['post_id'=>$post->id,'views'=>3]);
        Storage::disk('public')->assertExists('covers/retained.png');
        $this->get(route('admin.posts.trash'))->assertOk()->assertSee($post->title);
        $this->get(route('admin.posts.index'))->assertViewHas('posts',fn($p)=>!$p->contains('id',$post->id));
    }

    public function test_trashed_post_is_hidden_from_all_reading_and_editing_routes(): void
    {
        $post=$this->published(); $this->reader()->bookmarks()->syncWithoutDetaching([$post->id]);
        $post->delete();
        $this->get(route('posts.show',$post->slug))->assertNotFound();
        $this->get('/bai-viet')->assertViewHas('posts',fn($p)=>!$p->contains('id',$post->id));
        $this->get('/')->assertViewHas('featured',fn($featured)=>!$featured || $featured['id']!==$post->id);
        $this->actingAs($this->reader())->get(route('saved'))->assertViewHas('posts',fn($p)=>!$p->contains('id',$post->id));
        $this->post(route('comments.store',$post->id),['body'=>'Not allowed'])->assertNotFound();
        $this->post(route('bookmarks.store',$post->id))->assertNotFound();
        $this->actingAs($this->admin())->get(route('admin.posts.edit',$post->id))->assertNotFound();
        $this->get(route('admin.posts.preview',$post->id))->assertNotFound();
    }

    public function test_restore_keeps_content_relations_slug_and_counts_but_returns_to_draft(): void
    {
        $post=$this->published(); $content=$post->content;
        $post->increment('views_count',5);
        $this->reader()->bookmarks()->syncWithoutDetaching([$post->id]);
        $tagIds=$post->tags->pluck('id')->all();
        $post->delete();
        $this->actingAs($this->admin())->patch(route('admin.posts.restore',$post->id))->assertRedirect(route('admin.posts.edit',$post->id));
        $restored=Post::findOrFail($post->id);
        $this->assertSame('draft',$restored->status);
        $this->assertNull($restored->published_at);
        $this->assertNull($restored->deleted_at);
        $this->assertSame($content,$restored->content);
        $this->assertSame($post->slug,$restored->slug);
        $this->assertSame(5,(int)$restored->views_count);
        $this->assertSame($tagIds,$restored->tags->pluck('id')->all());
        $this->assertDatabaseHas('bookmarks',['post_id'=>$post->id]);
        $this->get(route('posts.show',$restored->slug))->assertNotFound();
        $this->get(route('admin.posts.edit',$restored))->assertOk();
        $this->patch(route('admin.posts.restore',$restored->id))->assertNotFound();
    }

    public function test_permanent_delete_requires_confirmation_and_only_accepts_trashed_posts(): void
    {
        $post=$this->published();
        $this->actingAs($this->admin())->delete(route('admin.posts.force-destroy',$post->id),['confirm_delete'=>1])->assertNotFound();
        $this->patch(route('admin.posts.restore',$post->id))->assertNotFound();
        $post->delete();
        $this->deleteJson(route('admin.posts.force-destroy',$post->id))->assertUnprocessable()->assertJsonValidationErrors('confirm_delete');
        $this->deleteJson(route('admin.posts.force-destroy',$post->id),['confirm_delete'=>0])->assertUnprocessable();
        $this->assertDatabaseHas('posts',['id'=>$post->id]);
        $this->delete(route('admin.posts.force-destroy',$post->id),['confirm_delete'=>1])->assertRedirect(route('admin.posts.trash'));
        $this->assertDatabaseMissing('posts',['id'=>$post->id]);
        $this->delete(route('admin.posts.force-destroy',$post->id),['confirm_delete'=>1])->assertNotFound();
    }

    public function test_permanent_delete_cascades_daily_history_and_keeps_shared_demo_images(): void
    {
        $post=Post::published()->whereNotNull('cover_path')->firstOrFail();
        $path=public_path($post->cover_path);
        $this->assertFileExists($path);
        PostDailyView::create(['post_id'=>$post->id,'view_date'=>now()->toDateString(),'views'=>4]);
        $post->delete();
        $this->actingAs($this->admin())->delete(route('admin.posts.force-destroy',$post->id),['confirm_delete'=>1])->assertRedirect();
        $this->assertDatabaseMissing('post_daily_views',['post_id'=>$post->id]);
        $this->assertFileExists($path);
    }

    public function test_trash_search_paginates_and_validates_input(): void
    {
        $post=$this->published();
        for($i=0;$i<12;$i++) {
            $copy=$post->replicate()->fill(['title'=>'Trash sample '.$i,'slug'=>'trash-sample-'.$i]); $copy->save(); $copy->delete();
        }
        $this->actingAs($this->admin())->get('/admin/bai-viet/thung-rac?q=Trash')->assertOk()->assertViewHas('posts',fn($p)=>$p->total()===12 && $p->count()===10)->assertSee('q=Trash',false);
        $this->get('/admin/bai-viet/thung-rac?q=Trash&page=2')->assertViewHas('posts',fn($p)=>$p->count()===2);
        $this->getJson('/admin/bai-viet/thung-rac?q[]=bad')->assertUnprocessable();
        $this->get('/admin/bai-viet/thung-rac?q=missing')->assertOk()->assertSee('Không tìm thấy bài phù hợp');
    }

    public function test_trashed_posts_reserve_slugs_for_restore_and_generated_slug_skips_them(): void
    {
        $post=$this->published();
        $post->update(['title'=>'Trash reserved slug','slug'=>'trash-reserved-slug']);
        $post->delete();
        $data=['title'=>$post->title,'content'=>$post->content,'category_id'=>$post->category_id,'status'=>'draft'];
        $this->actingAs($this->admin())->postJson(route('admin.posts.store'),[...$data,'slug'=>$post->slug])->assertUnprocessable()->assertJsonValidationErrors('slug');
        $this->post(route('admin.posts.store'),$data)->assertRedirect();
        $this->assertDatabaseHas('posts',['slug'=>$post->slug.'-2','deleted_at'=>null]);
    }

    public function test_category_with_only_trashed_posts_cannot_be_deleted(): void
    {
        $category=Category::create(['name'=>'Trash category','slug'=>'trash-category']);
        $post=$this->published(); $post->update(['category_id'=>$category->id]); $post->delete();
        $this->actingAs($this->admin())->delete(route('admin.categories.destroy',$category))->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories',['id'=>$category->id]);
    }

    public function test_trashed_comments_are_not_reviewed_or_modified_until_restored(): void
    {
        $post=$this->published();
        $comment=Comment::create(['post_id'=>$post->id,'user_id'=>$this->reader()->id,'body'=>'Trash pending comment','status'=>'pending']);
        $post->delete();
        $this->actingAs($this->admin())->get(route('admin.comments.index'))->assertOk()->assertDontSee('Trash pending comment');
        $this->patch(route('admin.comments.update',$comment),['status'=>'approved'])->assertNotFound();
        $this->delete(route('admin.comments.destroy',$comment))->assertNotFound();
        $this->actingAs($this->reader())->delete(route('comments.destroy',$comment))->assertNotFound();
        $this->actingAs($this->admin())->patch(route('admin.posts.restore',$post->id))->assertRedirect();
        $this->get(route('admin.comments.index'))->assertOk()->assertSee('Trash pending comment');
    }

    public function test_seeder_does_not_recreate_or_restore_a_trashed_demo_post(): void
    {
        $post=$this->published(); $post->delete();
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(5,Post::withTrashed()->count());
        $this->assertSoftDeleted('posts',['id'=>$post->id]);
    }
}
