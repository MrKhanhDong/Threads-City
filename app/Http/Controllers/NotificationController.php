<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\ContentReport;
use App\Models\Post;
use App\Notifications\Mentioned;
use App\Notifications\ReportHandled;
use App\Notifications\ThreadInteractionNotification;
use App\Services\CommentThreads;
use App\Services\Mentions;
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
        $tab = $request->query('tab', 'all');
        if (in_array($tab, ['mention', 'mentions'], true)) {
            $query->where(fn ($items) => $items->where('type', Mentioned::class)->orWhere('data->action', 'mention'));
        } elseif (in_array($tab, ['like', 'likes', 'reply', 'replies', 'follow', 'follows', 'repost', 'reposts'], true)) {
            $action = ['likes' => 'like', 'replies' => 'reply', 'follows' => 'follow', 'reposts' => 'repost'][$tab] ?? $tab;
            $query->whereIn('data->action', $action === 'repost' ? ['repost', 'quote'] : [$action]);
        }
        $unread = (clone $query)->whereNull('read_at')->count();
        if ($request->expectsJson() && $request->boolean('summary')) {
            return response()->json(['unread' => $unread])->header('Cache-Control', 'private, no-store');
        }
        $notifications = $query->paginate($request->boolean('preview') ? 8 : 20);
        $comments = Comment::whereIn('id', $notifications->map(fn ($notification): int => (int) ($notification->data['comment_id'] ?? 0))->filter())
            ->with(['user', 'post', 'parent.user'])->get()->keyBy('id');
        $reportResults = ContentReport::where('reporter_id', $request->user()->id)
            ->whereIn('id', $notifications->map(fn ($notification): int => (int) ($notification->data['report_id'] ?? 0))->filter())
            ->get(['id', 'status', 'action', 'reporter_message'])->keyBy('id');
        $mentionRecords = app(Mentions::class)->visibleTo($request->user())
            ->whereIn('id', $notifications->map(fn ($notification): int => (int) ($notification->data['mention_id'] ?? 0))->filter())->get();
        $mentionedPosts = Post::whereIn('id', $mentionRecords->pluck('post_id')->filter())->with('author')->get()->keyBy('id');
        $mentionedComments = Comment::whereIn('id', $mentionRecords->pluck('comment_id')->filter())->with('user')->get()->keyBy('id');
        $mentionContexts = $mentionRecords->mapWithKeys(fn (object $mention): array => [$mention->id => ['actor' => $mention->comment_id ? $mentionedComments->get($mention->comment_id)?->user : $mentionedPosts->get($mention->post_id)?->author, 'is_comment' => $mention->comment_id !== null]]);
        if ($request->expectsJson()) {
            $view = $request->boolean('preview') ? 'components.notification-preview' : 'components.notification-items';

            return response()->json(['unread' => $unread, 'html' => view($view, compact('notifications', 'comments', 'reportResults', 'mentionContexts'))->render()])
                ->header('Cache-Control', 'private, no-store');
        }

        return view('account.notifications', compact('notifications', 'comments', 'reportResults', 'unread', 'mentionContexts', 'tab'));
    }

    public function open(Request $request, string $id, CommentThreads $threads, UserNotifications $inbox): RedirectResponse
    {
        $notification = $inbox->forUser($request->user())->whereKey($id)->firstOrFail();
        if ($notification->type === ThreadInteractionNotification::class) {
            $notification->markAsRead();

            return redirect($notification->data['url'] ?? route('notifications.index'));
        }
        if ($notification->type === Mentioned::class) {
            $mention = app(Mentions::class)->visibleTo($request->user())->where('id', $notification->data['mention_id'])->firstOrFail();
            $url = $mention->comment_id ? $threads->url(Comment::findOrFail($mention->comment_id), $request->user()->id) : route('threads.show', $mention->post_id);
            $notification->markAsRead();

            return redirect($url);
        }
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

    public function unreadCount(Request $request, UserNotifications $inbox): JsonResponse
    {
        return response()->json(['unread' => $inbox->forUser($request->user())->whereNull('read_at')->count()])->header('Cache-Control', 'private, no-store');
    }
}
