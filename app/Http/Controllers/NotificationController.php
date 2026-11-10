<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\ContentReport;
use App\Notifications\ReportHandled;
use App\Services\CommentThreads;
use App\Services\UserNotifications;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request, UserNotifications $inbox): View|JsonResponse
    {
        $query = $inbox->forUser($request->user());
        $unread = (clone $query)->whereNull('read_at')->count();
        if ($request->expectsJson() && $request->boolean('summary')) {
            return response()->json(['unread' => $unread])->header('Cache-Control', 'private, no-store');
        }
        $notifications = $query->paginate(20);
        $comments = Comment::whereIn('id', $notifications->map(fn ($notification): int => (int) ($notification->data['comment_id'] ?? 0))->filter())
            ->with(['user', 'post', 'parent.user'])->get()->keyBy('id');
        $reportResults = ContentReport::where('reporter_id', $request->user()->id)
            ->whereIn('id', $notifications->map(fn ($notification): int => (int) ($notification->data['report_id'] ?? 0))->filter())
            ->get(['id', 'status', 'action', 'reporter_message'])->keyBy('id');
        if ($request->expectsJson()) {
            return response()->json(['unread' => $unread, 'html' => view('components.notification-items', compact('notifications', 'comments', 'reportResults'))->render()])
                ->header('Cache-Control', 'private, no-store');
        }

        return view('account.notifications', compact('notifications', 'comments', 'reportResults', 'unread'));
    }

    public function open(Request $request, string $id, CommentThreads $threads, UserNotifications $inbox): RedirectResponse
    {
        $notification = $inbox->forUser($request->user())->whereKey($id)->firstOrFail();
        if ($notification->type === ReportHandled::class) {
            $report = ContentReport::where('reporter_id', $request->user()->id)->findOrFail($notification->data['report_id']);
            $notification->markAsRead();

            return redirect()->route('reports.show', $report);
        }
        $comment = Comment::findOrFail($notification->data['comment_id']);
        $url = $threads->url($comment, $request->user()->id);
        $notification->markAsRead();

        return redirect($url);
    }

    public function readAll(Request $request, UserNotifications $inbox): RedirectResponse|JsonResponse
    {
        $inbox->forUser($request->user())->whereNull('read_at')->update(['read_at' => now()]);
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Đã đánh dấu tất cả thông báo là đã đọc.']);
        }

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }
}
