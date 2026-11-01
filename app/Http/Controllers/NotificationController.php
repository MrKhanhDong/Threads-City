<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $tab = $request->query('tab', 'all');
        $unread = $user->unreadNotifications()->count();

        if ($request->wantsJson() && $request->boolean('summary')) {
            return response()->json(['unread' => $unread])->header('Cache-Control', 'private, no-store');
        }

        if ($request->wantsJson() && $request->boolean('preview')) {
            $notifications = $user->notifications()->latest()->limit(6)->get();

            return response()->json([
                'unread' => $unread,
                'html' => view('components.notification-items', compact('notifications'))->render(),
            ])->header('Cache-Control', 'private, no-store');
        }

        $query = $user->notifications();

        if (in_array($tab, ['like', 'likes'], true)) {
            $query->where('data->action', 'like');
        } elseif (in_array($tab, ['reply', 'replies'], true)) {
            $query->where('data->action', 'reply');
        } elseif (in_array($tab, ['mention', 'mentions'], true)) {
            $query->where('data->action', 'mention');
        } elseif (in_array($tab, ['follow', 'follows'], true)) {
            $query->where('data->action', 'follow');
        } elseif (in_array($tab, ['repost', 'reposts'], true)) {
            $query->whereIn('data->action', ['repost', 'quote']);
        }

        $notifications = $query->latest()->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'unread' => $unread,
                'notifications' => $notifications,
            ]);
        }

        return view('account.notifications', compact('notifications', 'unread', 'tab'));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $unread = $request->user()->unreadNotifications()->count();

        return response()->json([
            'unread' => $unread,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        $targetUrl = $notification->data['url'] ?? route('notifications.index');

        return redirect($targetUrl);
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Đã đánh dấu tất cả thông báo là đã đọc.']);
        }

        return back()->with('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    }
}
