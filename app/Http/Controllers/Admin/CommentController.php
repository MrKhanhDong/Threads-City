<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentReport;
use App\Notifications\ModerationDecision;
use App\Services\ModerationAudit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $data = $request->validate(['status' => 'required|in:approved,hidden,spam', 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $comment, $data): void {
            $target = Comment::lockForUpdate()->findOrFail($comment->id);
            $before = ['status' => $target->status];
            $target->update(['status' => $data['status']]);
            app(ModerationAudit::class)->record($request->user(), 'comment', $target->id, 'moderate_comment', $data['reason'], $before, ['status' => $target->status]);
            $target->user?->notify(new ModerationDecision('Bình luận #'.$target->id.' của bạn đã được cập nhật trạng thái: '.$target->status.'.', $data['reason']));
        });

        return back()->with('success', 'Đã cập nhật trạng thái bình luận.');
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        abort(403, 'Hãy ẩn bình luận để giữ nội dung phục vụ xem xét và khôi phục.');
    }

    public function resolve(Request $request, CommentReport $report): RedirectResponse
    {
        $data = $request->validate(['reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $report, $data): void {
            $target = CommentReport::lockForUpdate()->findOrFail($report->id);
            abort_if($target->resolved, 422, 'Báo cáo đã được xử lý.');
            $target->update(['resolved' => true]);
            app(ModerationAudit::class)->record($request->user(), 'comment_report', $target->id, 'resolve_comment_report', $data['reason'], ['resolved' => false], ['resolved' => true]);
            $target->user?->notify(new ModerationDecision('Báo cáo bình luận #'.$target->comment_id.' của bạn đã được xem xét.', $data['reason']));
        });

        return back()->with('success', 'Đã đánh dấu báo cáo được xử lý.');
    }
}
