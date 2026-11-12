<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Mentions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MentionController extends Controller
{
    public function index(Request $request, Mentions $mentions): JsonResponse
    {
        $data = $request->validate(['q' => 'nullable|string|max:60']);
        $term = $data['q'] ?? '';
        $users = $mentions->candidates($request->user())
            ->where(fn (Builder $query): Builder => $query->where('username', 'like', addcslashes($term, '%_\\').'%')->orWhere('name', 'like', addcslashes($term, '%_\\').'%'))
            ->orderBy('username')->limit(8)->get(['id', 'name', 'username']);

        return response()->json(['users' => $users->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name, 'handle' => $user->handle(), 'url' => route('mentions.profile', $user)])])
            ->header('Cache-Control', 'private, no-store');
    }

    public function profile(Request $request, User $user): RedirectResponse
    {
        abort_unless(! $user->is_locked && User::discoverableTo($request->user())->whereKey($user->id)->exists(), 404);

        return redirect()->route('profile.show', $user->username ?: $user->id);
    }
}
