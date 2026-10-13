<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentReport;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['status' => 'nullable|in:pending,approved,hidden,spam', 'q' => 'nullable|string|max:150']);
        $comments = Comment::whereHas('post')->with(['user', 'post', 'parent'])->when($data['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))->when($data['q'] ?? null, fn (Builder $query, string $search): Builder => $query->where('body', 'like', '%'.$search.'%'))->latest()->paginate(15)->withQueryString();

        return view('admin.comments', ['comments' => $comments, 'reports' => CommentReport::where('resolved', false)->with(['user', 'comment.user', 'comment.post'])->latest()->limit(30)->get()]);
    }

    public function update(Request $request, Comment $comment): RedirectResponse
    {
        $comment->update($request->validate(['status' => 'required|in:approved,hidden,spam']));

        return back()->with('success', 'Đã cập nhật trạng thái bình luận.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('success', 'Đã xóa bình luận. Các trả lời còn lại được giữ lại.');
    }

    public function resolve(CommentReport $report): RedirectResponse
    {
        $report->update(['resolved' => true]);

        return back()->with('success', 'Đã đánh dấu báo cáo được xử lý.');
    }
}
