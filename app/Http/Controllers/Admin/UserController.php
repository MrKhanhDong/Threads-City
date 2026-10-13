<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['q' => 'nullable|string|max:150']);
        $users = User::where('role', 'reader')->when($data['q'] ?? null, fn (Builder $query, string $search): Builder => $query->where(fn (Builder $query): Builder => $query->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))->withCount('comments')->latest()->paginate(20)->withQueryString();

        return view('admin.users', compact('users'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'reader', 403);
        $data = $request->validate(['is_locked' => 'required|boolean']);
        $user->is_locked = $data['is_locked'];
        $user->save();

        return back()->with('success', $user->is_locked ? 'Đã khóa độc giả; các phiên bị từ chối ở request tiếp theo.' : 'Đã mở khóa độc giả.');
    }
}
