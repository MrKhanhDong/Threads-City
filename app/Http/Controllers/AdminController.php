<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Services\PostWriter;
use App\Services\BlogAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index(Request $request, BlogAnalytics $analytics)
    {
        $filters = $request->validate(['range' => 'nullable|integer|in:7,30,90']);
        $report = $analytics->overview((int)($filters['range'] ?? 30));
        return view('admin.index', [
            'stats' => ['posts' => Post::count(), 'published' => Post::published()->count(), 'drafts' => Post::where('status', 'draft')->count(), 'pending' => $report['pendingCount'], 'readers' => User::where('role', 'reader')->count(), 'views' => Post::sum('views_count')],
            'analytics' => $report, 'trashCount' => Post::onlyTrashed()->count(),
            'recent' => Post::with('category')->latest()->take(5)->get(),
        ]);
    }
    public function posts(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:200', 'status' => 'nullable|in:draft,published,archived']);
        $query = Post::with('category')->withCount('comments')->latest('updated_at');
        if (!empty($filters['q'])) $query->where('title', 'like', '%'.$filters['q'].'%');
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        return view('admin.posts', ['posts' => $query->paginate(10)->withQueryString(), 'filters' => $filters, 'trashCount' => Post::onlyTrashed()->count()]);
    }
    public function create()
    {
        return $this->editor(new Post(['status' => 'draft']));
    }
    public function edit(Post $post) { return $this->editor($post); }
    private function editor(Post $post)
    {
        return view('admin.editor', ['article' => $post->load('tags'), 'editorCategories' => Category::where('is_active', true)->orderBy('name')->get(), 'editorTags' => Tag::orderBy('name')->get()]);
    }
    public function store(PostRequest $request, PostWriter $writer)
    {
        $post = $writer->save($request);
        return redirect()->route('admin.posts.edit', $post)->with('success', $post->status === 'published' ? 'Đã xuất bản bài viết.' : 'Đã lưu bài viết vào database.');
    }
    public function update(PostRequest $request, Post $post, PostWriter $writer)
    {
        $writer->save($request, $post);
        return back()->with('success', 'Đã cập nhật bài viết.');
    }
    public function destroy(Post $post)
    {
        DB::transaction(fn () => Post::lockForUpdate()->findOrFail($post->id)->delete());
        return redirect()->route('admin.posts.index')->with('success', 'Đã chuyển bài vào thùng rác. Bạn có thể khôi phục bài, ảnh và dữ liệu liên quan.');
    }
    public function trash(Request $request)
    {
        $filters = $request->validate(['q' => 'nullable|string|max:200']);
        $query = Post::onlyTrashed()->with('category')->withCount('comments')->latest('deleted_at');
        if (!empty($filters['q'])) $query->where('title', 'like', '%'.$filters['q'].'%');
        return view('admin.trash', ['posts' => $query->paginate(10)->withQueryString(), 'filters' => $filters]);
    }
    public function restore(string $id)
    {
        $post = DB::transaction(function () use ($id) {
            $post = Post::onlyTrashed()->lockForUpdate()->findOrFail($id);
            $post->forceFill(['status' => 'draft', 'published_at' => null])->restore();
            return $post;
        });
        return redirect()->route('admin.posts.edit', $post)->with('success', 'Đã khôi phục bài về bản nháp, giữ nguyên ảnh, thẻ, bình luận và lượt xem. Chọn Xuất bản khi bạn sẵn sàng.');
    }
    public function forceDestroy(Request $request, string $id)
    {
        $request->validate(['confirm_delete' => 'required|accepted']);
        $cover = DB::transaction(function () use ($id) {
            $post = Post::onlyTrashed()->lockForUpdate()->findOrFail($id);
            $cover = $post->cover_path;
            $post->forceDelete();
            return $cover;
        });
        if ($cover && str_starts_with($cover, 'covers/')) Storage::disk('public')->delete($cover);
        return redirect()->route('admin.posts.trash')->with('success', 'Đã xóa vĩnh viễn bài, ảnh upload, bình luận, lưu bài và thống kê theo ngày liên quan. Không thể khôi phục bằng thùng rác.');
    }
    public function comments(Request $request)
    {
        $filters = $request->validate(['status' => 'nullable|in:pending,approved,rejected']);
        $query = Comment::whereHas('post')->with(['post', 'user'])->latest();
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        return view('admin.comments', ['comments' => $query->paginate(15)->withQueryString()]);
    }
    public function moderate(Request $request, Comment $comment)
    {
        abort_unless($comment->post, 404);
        $data = $request->validate(['status' => 'required|in:pending,approved,rejected']);
        $comment->update($data);
        return back()->with('success', 'Đã cập nhật trạng thái bình luận.');
    }
    public function deleteComment(Comment $comment)
    {
        abort_unless($comment->post, 404);
        $comment->delete();
        return back()->with('success', 'Đã xóa bình luận.');
    }
    public function users()
    {
        return view('admin.users', ['users' => User::withCount('comments')->latest()->paginate(15)]);
    }
    public function lockUser(Request $request, User $user)
    {
        abort_if($user->role === 'admin' || $user->id === $request->user()->id, 403);
        $data = $request->validate(['is_locked' => 'required|boolean']);
        $user->update(['is_locked' => (bool) $data['is_locked']]);
        return back()->with('success', $user->is_locked ? 'Đã khóa độc giả. Phiên đăng nhập sẽ bị chặn ở request tiếp theo.' : 'Đã mở khóa độc giả.');
    }
    public function settings() { return view('admin.settings'); }
    public function updateSettings(Request $request)
    {
        $data = $request->validate(['blog_title' => 'required|string|max:100', 'headline' => 'nullable|string|max:120', 'bio' => 'nullable|string|max:500', 'about' => 'nullable|string|max:10000', 'website' => 'nullable|url:http,https|max:255', 'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', 'remove_avatar' => 'sometimes|boolean']);
        $owner = $request->user();
        $old = $owner->avatar_path;
        $new = $request->hasFile('avatar') ? $request->file('avatar')->store('avatars', 'public') : null;
        if ($request->hasFile('avatar') && !$new) throw new \RuntimeException('Không lưu được avatar.');
        try {
            $owner->fill(collect($data)->only(['blog_title', 'headline', 'bio', 'about', 'website'])->all());
            $owner->avatar_path = $new ?: ($request->boolean('remove_avatar') ? null : $old);
            $owner->save();
        } catch (\Throwable $error) {
            if ($new) Storage::disk('public')->delete($new);
            throw $error;
        }
        if ($old && $old !== $owner->avatar_path && str_starts_with($old, 'avatars/')) Storage::disk('public')->delete($old);
        return back()->with('success', 'Đã cập nhật thông tin blog.');
    }
}
