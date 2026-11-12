<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SafetyController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $blockedUsers = $user->blockedUsers()->orderBy('name')->paginate(15, ['users.*'], 'blocked_page');
        $mutedUsers = $user->mutedUsers()->orderBy('name')->paginate(15, ['users.*'], 'muted_page');
        $followRequests = User::discoverableTo($user)->where('is_locked', false)
            ->whereIn('id', DB::table('follow_requests')->where('target_id', $user->id)->select('requester_id'))
            ->orderBy('name')->paginate(15, ['users.*'], 'requests_page');

        return view('account.safety', compact('user', 'blockedUsers', 'mutedUsers', 'followRequests'));
    }

    public function privacy(Request $request): RedirectResponse
    {
        $data = $request->validate(['is_private' => 'required|boolean']);
        DB::transaction(function () use ($request, $data): void {
            $owner = User::lockForUpdate()->findOrFail($request->user()->id);
            $owner->is_private = $data['is_private'];
            $owner->save();

            if (! $owner->is_private) {
                $requesters = User::discoverableTo($owner)->where('is_locked', false)
                    ->whereIn('id', DB::table('follow_requests')->where('target_id', $owner->id)->select('requester_id'))->pluck('id');
                foreach ($requesters as $requesterId) {
                    DB::table('follows')->insertOrIgnore(['follower_id' => $requesterId, 'following_id' => $owner->id, 'created_at' => now()]);
                }
                DB::table('follow_requests')->where('target_id', $owner->id)->delete();
            }
        });

        return back()->with('success', $data['is_private'] ? 'Đã bật tài khoản riêng tư.' : 'Đã chuyển tài khoản sang công khai và chấp nhận các yêu cầu theo dõi đang chờ.');
    }

    public function mentions(Request $request): RedirectResponse
    {
        $data = $request->validate(['mention_policy' => 'required|in:everyone,following,nobody']);
        $user = $request->user();
        $user->mention_policy = $data['mention_policy'];
        $user->save();

        return back()->with('success', 'Đã cập nhật người được phép nhắc đến bạn.');
    }

    public function block(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->id === $user->id, 422, 'Bạn không thể chặn chính mình.');
        $blocking = ! $request->isMethod('delete');
        DB::transaction(function () use ($request, $user, $blocking): void {
            $this->lockPair($request->user(), $user);
            $blocks = DB::table('user_safety_blocks')->where('blocker_id', $request->user()->id)->where('blocked_id', $user->id);
            if (! $blocking) {
                $blocks->delete();
                DB::table('user_blocks')->where('user_id', $request->user()->id)->where('blocked_user_id', $user->id)->delete();

                return;
            }
            DB::table('user_blocks')->insertOrIgnore(['user_id' => $request->user()->id, 'blocked_user_id' => $user->id, 'created_at' => now()]);
            DB::table('user_safety_blocks')->insertOrIgnore(['blocker_id' => $request->user()->id, 'blocked_id' => $user->id, 'created_at' => now()]);
            DB::table('follows')->where(fn (Builder $pairs): Builder => $pairs->where('follower_id', $request->user()->id)->where('following_id', $user->id))
                ->orWhere(fn (Builder $pairs): Builder => $pairs->where('follower_id', $user->id)->where('following_id', $request->user()->id))->delete();
            DB::table('follow_requests')->where(fn (Builder $pairs): Builder => $pairs->where('requester_id', $request->user()->id)->where('target_id', $user->id))
                ->orWhere(fn (Builder $pairs): Builder => $pairs->where('requester_id', $user->id)->where('target_id', $request->user()->id))->delete();
        });

        return redirect()->route($blocking ? 'home' : 'safety.index')->with('success', $blocking ? 'Đã chặn tài khoản. Hai bên không thể xem hoặc tương tác với nhau.' : 'Đã bỏ chặn. Các lượt theo dõi trước đây không được khôi phục tự động.');
    }

    public function mute(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->id === $user->id, 422, 'Bạn không thể tắt tiếng chính mình.');
        $muting = ! $request->isMethod('delete');
        abort_if($muting && $request->user()->isBlockedWith($user), 404);
        if ($muting) {
            DB::table('user_mutes')->insertOrIgnore(['user_id' => $request->user()->id, 'muted_user_id' => $user->id, 'created_at' => now()]);
        } else {
            DB::table('user_mutes')->where('user_id', $request->user()->id)->where('muted_user_id', $user->id)->delete();
        }

        return redirect()->route($muting ? 'home' : 'safety.index')->with('success', $muting ? 'Đã tắt tiếng. Bài viết của tài khoản này sẽ ẩn khỏi trang chủ.' : 'Đã bỏ tắt tiếng.');
    }

    public function followRequest(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['decision' => 'required|in:accept,reject']);
        DB::transaction(function () use ($request, $user, $data): void {
            $this->lockPair($request->user(), $user);
            abort_if($request->user()->isBlockedWith($user) || $user->is_locked, 404);
            $pending = DB::table('follow_requests')->where('requester_id', $user->id)->where('target_id', $request->user()->id);
            abort_unless((clone $pending)->exists(), 404);
            if ($data['decision'] === 'accept') {
                DB::table('follows')->insertOrIgnore(['follower_id' => $user->id, 'following_id' => $request->user()->id, 'created_at' => now()]);
            }
            $pending->delete();
        });

        return back()->with('success', $data['decision'] === 'accept' ? 'Đã chấp nhận yêu cầu theo dõi.' : 'Đã từ chối yêu cầu theo dõi.');
    }

    private function lockPair(User $first, User $second): void
    {
        User::whereKey([$first->id, $second->id])->orderBy('id')->lockForUpdate()->get();
    }
}
