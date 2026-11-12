<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserProfileController extends Controller
{
    public function connections(Request $request, User $user, string $kind): View|JsonResponse
    {
        abort_unless(in_array($kind, ['followers', 'following'], true), 404);
        abort_unless(! $user->is_locked && User::discoverableTo($request->user())->whereKey($user->id)->exists() && $user->canViewPosts($request->user()), 404);
        $people = $user->{$kind}()->discoverableTo($request->user())->where('is_locked', false)
            ->withExists([
                'followers as viewer_following' => fn (Builder $followers): Builder => $followers->whereKey($request->user()?->id ?? 0),
            ])->orderBy('users.name')->orderBy('users.id')->paginate(20);
        $requestedIds = $request->user() ? DB::table('follow_requests')->where('requester_id', $request->user()->id)->whereIn('target_id', $people->pluck('id'))->pluck('target_id')->all() : [];
        if ($request->expectsJson()) {
            return response()->json(['html' => view('profile.connection-items', compact('people', 'requestedIds'))->render(), 'nextPageUrl' => $people->nextPageUrl()])->header('Cache-Control', 'private, no-store');
        }

        return view('profile.connections', compact('user', 'kind', 'people', 'requestedIds'));
    }

    public function show(Request $request, string $username): View
    {
        $user = User::discoverableTo($request->user())->where('username', $username)->first();
        if (! $user && ctype_digit($username)) {
            $user = User::discoverableTo($request->user())->find($username);
        }
        abort_unless($user, 404);
        $data = $request->validate(['tab' => 'nullable|in:threads,replies,reposts,likes']);
        $tab = $data['tab'] ?? 'threads';
        if ($tab === 'likes') {
            abort_unless($request->user()?->id === $user->id, 403);
            $query = $user->likedPosts()->published()->visibleTo($request->user())->withThreadContext($request->user())->latest('post_likes.created_at');
        } else {
            $query = Post::published()->visibleTo($request->user())->withThreadContext($request->user())->where('user_id', $user->id)->latest('published_at')->latest('id');
            if ($tab === 'replies') {
                $query->whereNotNull('parent_id');
            } elseif ($tab === 'reposts') {
                $query->where('type', 'repost');
            } else {
                $query->whereNull('parent_id')->where('type', '!=', 'repost');
            }
        }
        $threads = $query->paginate(15)->withQueryString();
        $followersCount = $user->followers()->count();
        $followingCount = $user->following()->count();
        $following = $request->user()?->isFollowing($user) ?? false;
        $requested = $request->user()?->hasRequestedToFollow($user) ?? false;
        $canViewPosts = $user->canViewPosts($request->user());
        $muted = $request->user()?->mutedUsers()->whereKey($user->id)->exists() ?? false;

        return view('profile.show', compact('user', 'threads', 'tab', 'followersCount', 'followingCount', 'following', 'requested', 'canViewPosts', 'muted'));
    }
}
