<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentReport;
use App\Notifications\ReportHandled;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModerationController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['status' => 'nullable|in:pending,resolved,dismissed,all']);
        $status = $data['status'] ?? 'pending';
        $reports = ContentReport::with(['reporter', 'post.author', 'reportedUser', 'handler'])
            ->when($status !== 'all', fn (Builder $reports): Builder => $reports->where('status', $status))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.moderation', compact('reports', 'status'));
    }

    public function update(Request $request, ContentReport $report): RedirectResponse
    {
        $data = $request->validate(['action' => 'required|in:resolve,dismiss,hide_thread,lock_user', 'admin_note' => 'nullable|string|max:1000', 'reporter_message' => 'nullable|string|max:1000']);
        DB::transaction(function () use ($request, $report, $data): void {
            $lockedReport = ContentReport::lockForUpdate()->findOrFail($report->id);
            abort_unless($lockedReport->status === 'pending', 422, 'Báo cáo này đã được xử lý.');
            if ($data['action'] === 'hide_thread') {
                abort_unless($lockedReport->post, 422, 'Báo cáo này không có bài viết để ẩn.');
                if (! $lockedReport->post->trashed()) {
                    $lockedReport->post->delete();
                }
            }
            if ($data['action'] === 'lock_user') {
                $target = $lockedReport->reportedUser ?? $lockedReport->post?->author;
                abort_unless($target && ! $target->isAdmin(), 403, 'Không thể khóa tài khoản quản trị viên.');
                $target->is_locked = true;
                $target->save();
            }
            $lockedReport->forceFill([
                'status' => $data['action'] === 'dismiss' ? 'dismissed' : 'resolved',
                'action' => $data['action'], 'admin_note' => $data['admin_note'] ?? null, 'reporter_message' => $data['reporter_message'] ?? null,
                'handled_by' => $request->user()->id, 'handled_at' => now(),
            ])->save();
            $lockedReport->reporter?->notify(new ReportHandled($lockedReport->id));
        });

        return back()->with('success', 'Đã xử lý báo cáo.');
    }
}
