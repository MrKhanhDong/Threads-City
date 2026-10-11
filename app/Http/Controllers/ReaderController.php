<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class ReaderController extends Controller
{
    public function save(Request $request, Post $post)
    {
        abort_unless(Post::published()->whereKey($post->id)->exists(), 404);
        $request->user()->bookmarks()->syncWithoutDetaching([$post->id]);
        return back()->with('success', 'Đã lưu bài viết vào tài khoản của bạn.');
    }
    public function unsave(Request $request, Post $post)
    {
        $request->user()->bookmarks()->detach($post->id);
        return back()->with('success', 'Đã bỏ bài viết khỏi Đọc sau.');
    }
    public function comment(Request $request, Post $post)
    {
        abort_unless(Post::published()->whereKey($post->id)->exists(), 404);
        $data = $request->validate(['body' => 'required|string|min:2|max:2000']);
        $post->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body'], 'status' => $request->user()->isAdmin() ? 'approved' : 'pending']);
        return redirect()->route('posts.show', $post->slug)->with('success', $request->user()->isAdmin() ? 'Đã gửi bình luận.' : 'Đã gửi lời nhắn. Bình luận sẽ xuất hiện sau khi chủ blog duyệt.');
    }
    public function destroyComment(Request $request, Comment $comment)
    {
        abort_unless($comment->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
        abort_unless($comment->post, 404);
        $comment->delete();
        return back()->with('success', 'Đã xóa bình luận.');
    }
}
