<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReaderController extends Controller
{
    public function saved(Request $request): View
    {
        return view('blog.archive', ['posts' => $request->user()->bookmarks()->published()->with(['cover', 'author', 'categories', 'tags'])->latest('bookmarks.created_at')->paginate(9), 'heading' => 'Bài viết đã lưu', 'subtitle' => 'Góc đọc nhỏ của riêng bạn.', 'filters' => []]);
    }

    public function bookmark(Request $request, string $id): RedirectResponse
    {
        $post = Post::published()->findOrFail($id);
        $request->user()->bookmarks()->syncWithoutDetaching([$post->id => ['created_at' => now()]]);

        return back()->with('success', 'Đã lưu bài vào góc đọc.');
    }

    public function unbookmark(Request $request, string $id): RedirectResponse
    {
        $request->user()->bookmarks()->detach($id);

        return back()->with('success', 'Đã bỏ lưu bài.');
    }

    public function like(Request $request, string $id): RedirectResponse
    {
        DB::transaction(function () use ($request, $id): void {
            $post = Post::published()->lockForUpdate()->findOrFail($id);
            DB::table('post_likes')->insertOrIgnore([
                'post_id' => $post->id,
                'user_id' => $request->user()->id,
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Cảm ơn bạn đã thích câu chuyện.');
    }

    public function unlike(Request $request, string $id): RedirectResponse
    {
        DB::transaction(function () use ($request, $id): void {
            $post = Post::published()->lockForUpdate()->findOrFail($id);
            $post->likers()->detach($request->user()->id);
        });

        return back()->with('success', 'Đã bỏ thích bài viết.');
    }

    public function comment(CommentRequest $request, string $id): RedirectResponse
    {
        $post = Post::published()->findOrFail($id);
        $data = $request->validated();
        if (! empty($data['parent_id'])) {
            $parent = Comment::where('post_id', $post->id)->where('status', 'approved')->findOrFail($data['parent_id']);
            $depth = 1;
            while ($parent->parent_id) {
                $parent = $parent->parent;
                abort_unless($parent && $parent->status === 'approved', 404);
                $depth++;
            }
            if ($depth > config('blog.reply_depth')) {
                throw ValidationException::withMessages(['parent_id' => 'Chuỗi trả lời đã đủ sâu. Hãy bắt đầu một bình luận mới.']);
            }
        }
        $comment = $post->comments()->create([...$data, 'user_id' => $request->user()->id, 'status' => 'approved']);

        return back()->withFragment('comment-'.$comment->id)->with('success', $comment->parent_id ? 'Đã gửi trả lời.' : 'Đã gửi bình luận.');
    }

    public function updateComment(CommentRequest $request, Comment $comment): RedirectResponse
    {
        Gate::authorize('update', $comment);
        abort_unless($comment->post && Post::published()->whereKey($comment->post_id)->exists(), 404);
        $comment->update(['body' => $request->validated('body')]);

        return back()->withFragment('comment-'.$comment->id)->with('success', 'Đã sửa bình luận. Nội dung đã bị quản trị viên ẩn sẽ không tự công khai lại.');
    }

    public function deleteComment(Comment $comment): RedirectResponse
    {
        Gate::authorize('delete', $comment);
        abort_unless($comment->post, 404);
        $comment->delete();

        return back()->with('success', 'Đã xóa bình luận của bạn.');
    }

    public function report(Request $request, Comment $comment): RedirectResponse
    {
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000']);
        abort_unless($comment->status === 'approved' && Post::published()->whereKey($comment->post_id)->exists(), 404);
        CommentReport::updateOrCreate(['comment_id' => $comment->id, 'user_id' => $request->user()->id], [...$data, 'resolved' => false]);

        return back()->with('success', 'Đã gửi báo cáo đến quản trị viên.');
    }
}
