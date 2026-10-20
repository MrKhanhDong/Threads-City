<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Models\Post;
use App\Notifications\CommentReplied;
use App\Services\CommentThreads;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReaderController extends Controller
{
    public function saved(Request $request): View
    {
        $threads = $request->user()->bookmarks()
            ->published()
            ->withThreadContext($request->user())
            ->latest('bookmarks.created_at')
            ->paginate(15);

        return view('threads.saved', compact('threads'));
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

    public function comment(CommentRequest $request, string $id, CommentThreads $threads): RedirectResponse
    {
        $post = Post::published()->findOrFail($id);
        $data = $request->validated();
        $comment = DB::transaction(function () use ($request, $data, $post, $threads): Comment {
            $parent = null;
            if (! empty($data['parent_id'])) {
                $parent = Comment::where('post_id', $post->id)->where('status', 'approved')->lockForUpdate()->findOrFail($data['parent_id']);
                abort_unless($threads->root($parent) !== null, 404);
            }
            $comment = $post->comments()->create([...$data, 'user_id' => $request->user()->id, 'status' => 'approved']);
            if ($parent && $parent->user_id !== $request->user()->id) {
                $parent->user->notify(new CommentReplied($comment->id));
            }

            return $comment;
        });

        return redirect($threads->url($comment, $request->user()->id))->with('success', $comment->parent_id ? 'Đã gửi trả lời.' : 'Đã gửi bình luận.');
    }

    public function updateComment(CommentRequest $request, Comment $comment, CommentThreads $threads): RedirectResponse
    {
        Gate::authorize('update', $comment);
        abort_unless($comment->post && Post::published()->whereKey($comment->post_id)->exists(), 404);
        $comment->update(['body' => $request->validated('body')]);

        $response = $threads->root($comment, $request->user()->id) !== null
            ? redirect($threads->url($comment, $request->user()->id))
            : back()->withFragment('comment-'.$comment->id);

        return $response->with('success', 'Đã sửa bình luận. Nội dung đã bị quản trị viên ẩn sẽ không tự công khai lại.');
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
