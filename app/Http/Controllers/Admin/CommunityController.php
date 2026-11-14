<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\CommunityModeration;
use App\Services\ModerationAudit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunityController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:150', 'author' => 'nullable|integer', 'id' => 'nullable|integer',
            'status' => 'nullable|in:active,hidden,deleted,all', 'kind' => 'nullable|in:post,reply',
            'from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from',
        ]);
        $status = $data['status'] ?? 'active';
        $posts = Post::withTrashed()->with(['author', 'media', 'cover', 'parent' => fn ($query) => $query->withTrashed(), 'repostOf' => fn ($query) => $query->withTrashed()])
            ->when($data['id'] ?? null, fn (Builder $posts, int $id): Builder => $posts->whereKey($id))
            ->when($status === 'active', fn (Builder $posts): Builder => $posts->whereNull('deleted_at'))
            ->when($status === 'hidden', fn (Builder $posts): Builder => $posts->whereNotNull('deleted_at')->whereNotNull('moderation_hidden_at'))
            ->when($status === 'deleted', fn (Builder $posts): Builder => $posts->whereNotNull('deleted_at')->whereNull('moderation_hidden_at'))
            ->when($data['q'] ?? null, fn (Builder $posts, string $search): Builder => $posts->where(fn (Builder $posts): Builder => $posts->where('content', 'like', '%'.$search.'%')->orWhere('title', 'like', '%'.$search.'%')->orWhereHas('author', fn (Builder $users): Builder => $users->where('username', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%'))))
            ->when($data['author'] ?? null, fn (Builder $posts, int $id): Builder => $posts->where('user_id', $id))
            ->when(($data['kind'] ?? '') === 'post', fn (Builder $posts): Builder => $posts->whereNull('parent_id'))
            ->when(($data['kind'] ?? '') === 'reply', fn (Builder $posts): Builder => $posts->whereNotNull('parent_id'))
            ->when($data['from'] ?? null, fn (Builder $posts, string $date): Builder => $posts->whereDate('created_at', '>=', $date))
            ->when($data['to'] ?? null, fn (Builder $posts, string $date): Builder => $posts->whereDate('created_at', '<=', $date))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.community', compact('posts', 'status'));
    }

    public function update(Request $request, int $id, CommunityModeration $moderation): RedirectResponse
    {
        $data = $request->validate(['action' => 'required|in:hide,restore', 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $id, $data, $moderation): void {
            $post = Post::withTrashed()->lockForUpdate()->findOrFail($id);
            if ($data['action'] === 'hide') {
                $moderation->hide($post, $request->user(), $data['reason']);
            } else {
                $moderation->restore($post, $request->user(), $data['reason']);
            }
        });

        return back()->with('success', 'Đã cập nhật kiểm duyệt và thông báo cho tác giả.');
    }

    public function history(Request $request): View
    {
        $data = $request->validate(['target_type' => 'nullable|in:user,post,comment,report,comment_report,settings', 'target_id' => 'nullable|integer', 'action' => 'nullable|string|max:40']);
        $logs = DB::table('moderation_logs')->leftJoin('users', 'users.id', '=', 'moderation_logs.admin_id')
            ->select('moderation_logs.*', 'users.name as admin_name')
            ->when($data['target_type'] ?? null, fn ($query, $type) => $query->where('target_type', $type))
            ->when($data['target_id'] ?? null, fn ($query, $id) => $query->where('target_id', $id))
            ->when($data['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->orderByDesc('moderation_logs.id')->paginate(30)->withQueryString();

        return view('admin.history', ['logs' => $logs, 'actions' => ModerationAudit::ACTIONS]);
    }
}
