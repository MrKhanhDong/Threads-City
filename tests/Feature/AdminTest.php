<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    private function data(array $extra=[]): array
    {
        return [...['title'=>'Bài viết thử nghiệm','slug'=>'','excerpt'=>'Lời mở đầu','content'=>"## Một tiêu đề\n\nNội dung đủ dài của bài viết.",'category_id'=>Category::where('is_active',true)->firstOrFail()->id,'status'=>'draft'],...$extra];
    }
    private function image(string $name='cover.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j3ioAAAAASUVORK5CYII='));
    }
    public function test_every_admin_page_is_protected_and_renders_for_owner(): void
    {
        $post=$this->published();
        $paths=['/admin','/admin/bai-viet','/admin/bai-viet/soan',"/admin/bai-viet/{$post->id}/sua","/admin/bai-viet/{$post->id}/xem-truoc",'/admin/chuyen-muc','/admin/the','/admin/binh-luan','/admin/doc-gia','/admin/cai-dat'];
        foreach($paths as $path) $this->get($path)->assertRedirect(route('login'));
        $this->actingAs($this->reader());
        foreach($paths as $path) $this->get($path)->assertForbidden();
        $this->actingAs($this->admin());
        foreach($paths as $path) $this->get($path)->assertOk();
    }
    public function test_reader_cannot_mutate_admin_resources(): void
    {
        $post=$this->published(); $cat=Category::firstOrFail(); $tag=Tag::firstOrFail(); $comment=Comment::firstOrFail();
        $this->actingAs($this->reader());
        foreach([
            ['post','admin.posts.store',[], $this->data()],
            ['patch','admin.posts.update',[$post],$this->data()],
            ['delete','admin.posts.destroy',[$post],[]],
            ['post','admin.categories.store',[],['name'=>'Unauthorized']],
            ['patch','admin.categories.update',[$cat],[]],
            ['delete','admin.categories.destroy',[$cat],[]],
            ['post','admin.tags.store',[],['name'=>'Unauthorized']],
            ['patch','admin.tags.update',[$tag],[]],
            ['delete','admin.tags.destroy',[$tag],[]],
            ['patch','admin.comments.update',[$comment],['status'=>'approved']],
            ['delete','admin.comments.destroy',[$comment],[]],
            ['patch','admin.users.update',[$this->admin()],['is_locked'=>true]],
            ['patch','admin.settings.update',[],['blog_title'=>'Unauthorized']],
        ] as [$verb,$route,$params,$data]) $this->{$verb}(route($route,$params),$data)->assertForbidden();
        $this->assertDatabaseMissing('categories',['name'=>'Unauthorized']);
        $this->assertDatabaseHas('posts',['id'=>$post->id]);
    }
    public function test_owner_creates_draft_with_tags_and_unique_generated_slugs(): void
    {
        $this->actingAs($this->admin());
        $tag=Tag::firstOrFail();
        $this->post(route('admin.posts.store'),$this->data(['tags'=>[$tag->id], 'user_id'=>$this->reader()->id]))->assertRedirect();
        $post=Post::where('slug','bai-viet-thu-nghiem')->firstOrFail();
        $this->assertSame('draft',$post->status);
        $this->assertNull($post->published_at);
        $this->assertSame($this->admin()->id,$post->user_id);
        $this->assertSame([$tag->id],$post->tags->pluck('id')->all());
        $this->post(route('admin.posts.store'),$this->data())->assertRedirect();
        $this->assertDatabaseHas('posts',['slug'=>'bai-viet-thu-nghiem-2']);
        $this->get(route('admin.posts.preview',$post))->assertOk()->assertSee('Bản xem trước');
        $this->get(route('posts.show',$post->slug))->assertNotFound();
    }
    public function test_publish_edit_and_archive_changes_public_visibility(): void
    {
        $this->actingAs($this->admin())->post(route('admin.posts.store'),$this->data(['status'=>'published','is_featured'=>1]))->assertRedirect();
        $post=Post::where('slug','bai-viet-thu-nghiem')->firstOrFail();
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->is_featured);
        $firstDate=$post->published_at->toDateTimeString();
        $this->get(route('posts.show',$post->slug))->assertOk()->assertSee('Bài viết thử nghiệm');
        $this->patch(route('admin.posts.update',$post),$this->data(['title'=>'Tiêu đề đã sửa','slug'=>$post->slug,'status'=>'published']))->assertRedirect();
        $this->assertSame($firstDate,$post->fresh()->published_at->toDateTimeString());
        $this->assertFalse($post->fresh()->is_featured);
        $this->get(route('posts.show',$post->slug))->assertSee('Tiêu đề đã sửa');
        $this->patch(route('admin.posts.update',$post),$this->data(['slug'=>$post->slug,'status'=>'archived']))->assertRedirect();
        $this->get(route('posts.show',$post->slug))->assertNotFound();
        $this->assertNull($post->fresh()->published_at);
    }
    public function test_validation_rejects_duplicate_slugs_inactive_category_invalid_tags_and_html_files(): void
    {
        $post=$this->published(); $category=Category::create(['name'=>'Inactive','slug'=>'inactive','is_active'=>false]);
        $this->actingAs($this->admin())->postJson(route('admin.posts.store'),$this->data(['slug'=>$post->slug,'category_id'=>$category->id,'tags'=>[999999], 'status'=>'invalid']))->assertUnprocessable()->assertJsonValidationErrors(['slug','category_id','tags.0','status']);
        $this->postJson(route('admin.posts.store'),$this->data(['cover'=>UploadedFile::fake()->createWithContent('evil.svg','<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>')]))->assertUnprocessable()->assertJsonValidationErrors('cover');
        // Laravel fake files report MIME by filename; wrap the real temporary
        // file to verify MIME sniffing of PHP disguised as a JPEG.
        $fake = UploadedFile::fake()->createWithContent('fake.jpg','<?php echo 123; ?>');
        $disguised = new UploadedFile($fake->getPathname(), 'fake.jpg', 'image/jpeg', null, true);
        $this->postJson(route('admin.posts.store'),$this->data(['cover'=>$disguised]))->assertUnprocessable()->assertJsonValidationErrors('cover');
    }
    public function test_cover_upload_is_private_for_draft_then_served_and_replaced(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin())->post(route('admin.posts.store'),$this->data(['cover'=>$this->image()]))->assertRedirect();
        $post=Post::where('slug','bai-viet-thu-nghiem')->firstOrFail(); $old=$post->cover_path;
        Storage::disk('public')->assertExists($old);
        $this->get(route('media',['path'=>$old]))->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
        $this->app['auth']->forgetGuards();
        $this->get(route('media',['path'=>$old]))->assertNotFound();
        $this->actingAs($this->admin())->patch(route('admin.posts.update',$post),$this->data(['status'=>'published','cover'=>$this->image('replacement.png')]))->assertRedirect();
        $post->refresh();
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($post->cover_path);
        $this->app['auth']->forgetGuards();
        $this->get(route('media',['path'=>$post->cover_path]))->assertOk();
        $this->actingAs($this->admin())->patch(route('admin.posts.update',$post),$this->data(['status'=>'published','remove_cover'=>1]))->assertRedirect();
        Storage::disk('public')->assertMissing($post->cover_path);
        $this->assertNull($post->fresh()->cover_path);
    }
    public function test_media_rejects_php_svg_missing_files_and_path_traversal(): void
    {
        foreach(['/media/covers/test.php','/media/covers/test.svg','/media/covers/missing.png','/media/covers/..%2F..%2F.env','/media/.env'] as $path) $this->get($path)->assertNotFound();
    }
    public function test_deleting_post_cascades_comments_bookmarks_and_pivot_and_removes_uploaded_cover(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin())->post(route('admin.posts.store'),$this->data(['cover'=>$this->image(),'tags'=>[Tag::firstOrFail()->id],'status'=>'published']))->assertRedirect();
        $post=Post::where('slug','bai-viet-thu-nghiem')->firstOrFail(); $path=$post->cover_path;
        Comment::create(['post_id'=>$post->id,'user_id'=>$this->reader()->id,'body'=>'Cascade this comment','status'=>'pending']);
        $this->reader()->bookmarks()->attach($post);
        $this->delete(route('admin.posts.destroy',$post))->assertRedirect(route('admin.posts.index'));
        $this->assertSoftDeleted('posts', ['id'=>$post->id]);
        Storage::disk('public')->assertExists($path);
        $this->delete(route('admin.posts.force-destroy', $post->id), ['confirm_delete'=>1])->assertRedirect(route('admin.posts.trash'));
        foreach(['posts'=>'id','comments'=>'post_id','bookmarks'=>'post_id','post_tag'=>'post_id'] as $table=>$column) $this->assertDatabaseMissing($table,[$column=>$post->id]);
        Storage::disk('public')->assertMissing($path);
    }
    public function test_category_crud_unique_and_deletion_restricts_nonempty_category(): void
    {
        $data=['name'=>'Ghi chép mới','description'=>'Một mô tả','icon'=>'book','is_active'=>1];
        $this->actingAs($this->admin())->post(route('admin.categories.store'),$data)->assertRedirect();
        $item=Category::where('slug','ghi-chep-moi')->firstOrFail();
        $this->patch(route('admin.categories.update',$item),[...$data,'name'=>'Đã sửa','slug'=>$item->slug,'is_active'=>0])->assertRedirect();
        $this->assertFalse($item->fresh()->is_active);
        $this->postJson(route('admin.categories.store'),[...$data,'slug'=>$item->slug])->assertUnprocessable()->assertJsonValidationErrors('slug');
        $this->delete(route('admin.categories.destroy',Category::findOrFail($this->published()->category_id)))->assertSessionHasErrors('category');
        $this->delete(route('admin.categories.destroy',$item))->assertRedirect();
        $this->assertDatabaseMissing('categories',['id'=>$item->id]);
    }
    public function test_tag_crud_deleting_tag_keeps_posts(): void
    {
        $this->actingAs($this->admin())->post(route('admin.tags.store'),['name'=>'MySQL mới'])->assertRedirect();
        $tag=Tag::where('slug','mysql-moi')->firstOrFail();
        $post=$this->published(); $post->tags()->attach($tag);
        $this->patch(route('admin.tags.update',$tag),['name'=>'Database','slug'=>'database'])->assertRedirect();
        $this->delete(route('admin.tags.destroy',$tag))->assertRedirect();
        $this->assertDatabaseHas('posts',['id'=>$post->id]);
        $this->assertDatabaseMissing('post_tag',['tag_id'=>$tag->id]);
    }
    public function test_owner_can_moderate_reject_and_delete_comments(): void
    {
        $comment=Comment::where('status','pending')->firstOrFail();
        $this->actingAs($this->admin())->patch(route('admin.comments.update',$comment),['status'=>'rejected'])->assertRedirect();
        $this->assertSame('rejected',$comment->fresh()->status);
        $this->get('/admin/binh-luan?status=rejected')->assertOk()->assertSee($comment->body);
        $this->patchJson(route('admin.comments.update',$comment),['status'=>'anything'])->assertUnprocessable();
        $this->delete(route('admin.comments.destroy',$comment))->assertRedirect();
        $this->assertDatabaseMissing('comments',['id'=>$comment->id]);
    }
    public function test_locked_reader_session_is_revoked_and_admin_cannot_be_locked(): void
    {
        $reader=$this->reader(); $admin=$this->admin();
        $this->actingAs($admin)->patch(route('admin.users.update',$reader),['is_locked'=>1])->assertRedirect();
        $this->assertTrue($reader->fresh()->is_locked);
        $this->actingAs($reader->fresh())->get(route('saved'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->actingAs($admin)->patch(route('admin.users.update',$admin),['is_locked'=>1])->assertForbidden();
        $this->patch(route('admin.users.update',$reader),['is_locked'=>0])->assertRedirect();
        $this->assertFalse($reader->fresh()->is_locked);
    }
    public function test_settings_are_persistent_avatar_upload_removal_and_unsafe_url_rejected(): void
    {
        Storage::fake('public');
        $data=['blog_title'=>'Góc viết mới','headline'=>'Người viết','bio'=>'Mô tả mới','about'=>"Đoạn một\n\nĐoạn hai",'website'=>'https://example.com'];
        $this->actingAs($this->admin())->patch(route('admin.settings.update'),[...$data,'avatar'=>$this->image('avatar.png')])->assertRedirect();
        $admin=$this->admin(); $path=$admin->avatar_path;
        Storage::disk('public')->assertExists($path);
        $this->get('/')->assertOk()->assertSee('Góc viết mới');
        $this->get('/gioi-thieu')->assertOk()->assertSee('Đoạn hai');
        $this->patchJson(route('admin.settings.update'),[...$data,'website'=>'javascript:alert(1)'])->assertUnprocessable()->assertJsonValidationErrors('website');
        $this->patch(route('admin.settings.update'),[...$data,'remove_avatar'=>1])->assertRedirect();
        Storage::disk('public')->assertMissing($path);
    }
    public function test_admin_library_search_and_status_filters_use_database(): void
    {
        $this->actingAs($this->admin())->get('/admin/bai-viet?status=draft')->assertOk()->assertViewHas('posts',fn($p)=>$p->total()===1);
        $this->get('/admin/bai-viet?q=controller')->assertOk()->assertViewHas('posts',fn($p)=>$p->total()===1);
    }

    public function test_generated_slug_fits_column_even_when_transliteration_expands_title(): void
    {
        $this->actingAs($this->admin())->post(route('admin.posts.store'), $this->data(['title'=>str_repeat('ß', 200)]))->assertRedirect();
        $post = Post::where('title', str_repeat('ß', 200))->firstOrFail();
        $this->assertLessThanOrEqual(240, strlen($post->slug));
    }

    public function test_invalid_array_shaped_old_input_does_not_break_forms(): void
    {
        $this->withSession(['_old_input'=>['email'=>['bad'], 'name'=>['bad']]])->get(route('register'))->assertOk();
        $this->actingAs($this->admin())->withSession(['_old_input'=>['title'=>['bad'],'slug'=>['bad'],'category_id'=>['bad'],'tags'=>'bad']])->get(route('admin.editor'))->assertOk();
    }
}
