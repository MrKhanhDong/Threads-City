<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ModerationDecision;
use App\Services\CommunityModeration;
use App\Services\ModerationAudit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['q' => 'nullable|string|max:150', 'status' => 'nullable|in:active,locked,verified']);
        $users = User::query()->when($data['q'] ?? null, fn (Builder $query, string $search): Builder => $query->where(fn (Builder $query): Builder => $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhere('username', 'like', '%'.$search.'%')))
            ->when(($data['status'] ?? '') === 'active', fn (Builder $users): Builder => $users->notLocked())
            ->when(($data['status'] ?? '') === 'locked', fn (Builder $users): Builder => $users->where('is_locked', true)->where(fn (Builder $users): Builder => $users->whereNull('lock_expires_at')->orWhere('lock_expires_at', '>', now())))
            ->when(($data['status'] ?? '') === 'verified', fn (Builder $users): Builder => $users->where('is_verified', true))
            ->withCount(['comments', 'posts'])->latest()->paginate(20)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function verification(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['is_verified' => 'required|boolean', 'reason' => 'required|string|max:1000']);
        DB::transaction(function () use ($request, $user, $data): void {
            $target = User::lockForUpdate()->findOrFail($user->id);
            $before = ['is_verified' => $target->is_verified];
            $target->is_verified = $data['is_verified'];
            $target->save();
            app(ModerationAudit::class)->record($request->user(), 'user', $target->id, $target->is_verified ? 'verify_user' : 'unverify_user', $data['reason'], $before, ['is_verified' => $target->is_verified]);
        });
        $user->refresh();

        return back()->with('success', $user->is_verified ? 'Đã cấp tích xanh cho '.$user->name.'.' : 'Đã gỡ tích xanh của '.$user->name.'.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'reader', 403);
        $data = $request->validate(['is_locked' => 'required|boolean', 'reason' => 'required|string|max:1000', 'lock_expires_at' => 'nullable|date|after:now']);
        DB::transaction(function () use ($request, $user, $data): void {
            $target = User::lockForUpdate()->findOrFail($user->id);
            abort_unless($target->role === 'reader', 403);
            if ($data['is_locked']) {
                app(CommunityModeration::class)->lock($target, $request->user(), $data['reason'], $data['lock_expires_at'] ?? null);
            } else {
                $before = ['is_locked' => $target->is_locked, 'lock_expires_at' => $target->lock_expires_at?->toIso8601String()];
                $target->forceFill(['is_locked' => false, 'lock_reason' => null, 'lock_expires_at' => null])->save();
                app(ModerationAudit::class)->record($request->user(), 'user', $target->id, 'unlock_user', $data['reason'], $before, ['is_locked' => false]);
                $target->notify(new ModerationDecision('Tài khoản của bạn đã được mở khóa.', $data['reason']));
            }
        });
        $user->refresh();

        return back()->with('success', $user->is_locked ? 'Đã khóa độc giả; các phiên bị từ chối ở request tiếp theo.' : 'Đã mở khóa độc giả.');
    }
}
