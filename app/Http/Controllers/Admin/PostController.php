<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostRequest;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Services\PostWriter;
use App\Services\SeoAnalyzer;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => 'nullable|string|max:150', 'status' => 'nullable|in:draft,published,scheduled,trash', 'category' => 'nullable|integer|exists:categories,id']);
        $posts = ($filters['status'] ?? '') === 'trash' ? Post::onlyTrashed() : Post::query();
        $posts->when($filters['q'] ?? null, fn (Builder $query, string $search): Builder => $query->where('title', 'like', '%'.$search.'%'))
            ->when(($filters['status'] ?? '') !== 'trash' && ! empty($filters['status']), fn (Builder $query): Builder => $query->where('status', $filters['status']))
            ->when($filters['category'] ?? null, fn (Builder $query, int $id): Builder => $query->whereHas('categories', fn (Builder $query): Builder => $query->where('categories.id', $id)));

        return view('admin.posts.index', ['posts' => $posts->with(['author', 'categories'])->latest('updated_at')->paginate(15)->withQueryString(), 'filters' => $filters, 'categories' => Category::orderBy('name')->get(), 'trashCount' => Post::onlyTrashed()->count()]);
    }

    public function create(SeoAnalyzer $analyzer): View
    {
        Gate::authorize('create', Post::class);

        return $this->form(new Post(['title' => '', 'content' => '', 'status' => 'draft']), $analyzer);
    }

    public function seoChecks(Request $request, SeoAnalyzer $analyzer): JsonResponse
    {
        Gate::authorize('create', Post::class);
        $data = $request->validate([
            'title' => 'nullable|string|max:200', 'slug' => 'nullable|string|max:220',
            'excerpt' => 'nullable|string|max:2000', 'content' => 'nullable|string|max:1000000',
            'seo_title' => 'nullable|string|max:200', 'meta_description' => 'nullable|string|max:320',
            'focus_keyword' => 'nullable|string|max:150', 'cover_media_id' => 'nullable|integer|exists:media,id',
        ]);

        return response()->json(['checks' => $analyzer->analyze(new Post($data))]);
    }

    public function edit(Post $post, SeoAnalyzer $analyzer): View
    {
        Gate::authorize('update', $post);

        return $this->form($post->load(['categories', 'tags']), $analyzer);
    }

    private function form(Post $post, SeoAnalyzer $analyzer): View
    {
        return view('admin.posts.form', ['post' => $post, 'categories' => Category::orderBy('name')->get(), 'tags' => Tag::orderBy('name')->get(), 'media' => Media::latest()->get(), 'seoChecks' => $analyzer->analyze($post)]);
    }

    public function store(PostRequest $request, PostWriter $writer): RedirectResponse
    {
        Gate::authorize('create', Post::class);
        $post = $writer->save($request);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Đã lưu bài viết. Bạn có thể xem trước bản đã lưu.');
    }

    public function update(PostRequest $request, Post $post, PostWriter $writer): RedirectResponse
    {
        Gate::authorize('update', $post);
        $writer->save($request, $post);

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Đã lưu thay đổi và định dạng bài viết.');
    }

    public function autosave(PostRequest $request, Post $post, PostWriter $writer): JsonResponse
    {
        Gate::authorize('update', $post);
        abort_unless($post->status === 'draft' && $request->validated('status') === 'draft', 409, 'Tự lưu chỉ áp dụng cho bài nháp.');
        $saved = $writer->save($request, $post);

        return response()->json(['version' => $saved->lock_version, 'message' => 'Đã tự lưu bản nháp lúc '.now()->format('H:i')]);
    }

    public function destroy(Post $post): RedirectResponse
    {
        Gate::authorize('delete', $post);
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Đã chuyển bài vào thùng rác; bạn vẫn có thể khôi phục.');
    }

    public function restore(int $id): RedirectResponse
    {
        DB::transaction(function () use ($id): void {
            $post = Post::onlyTrashed()->lockForUpdate()->findOrFail($id);
            Gate::authorize('update', $post);
            $post->forceFill(['status' => 'draft', 'published_at' => null, 'lock_version' => $post->lock_version + 1])->save();
            $post->restore();
        });

        return back()->with('success', 'Đã khôi phục về bản nháp. Kiểm tra nội dung trước khi xuất bản lại.');
    }

    public function forceDelete(Request $request, int $id): RedirectResponse
    {
        $request->validate(['confirm' => 'required|accepted']);
        $post = Post::onlyTrashed()->findOrFail($id);
        Gate::authorize('delete', $post);
        $post->forceDelete();

        return back()->with('success', 'Đã xóa vĩnh viễn bài và dữ liệu liên quan. File trong thư viện được giữ lại.');
    }
}
