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
        $people = $user->{$kind}()->discoverableTo($request->user())->notLocked()
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
        $user = User::discoverableTo($request->user())->where(fn ($query) => $query->where('username', $username)->orWhere('id', ctype_digit($username) ? (int) $username : 0))->firstOrFail();

        $tab = $request->query('tab', 'threads');
        if ($tab === 'likes') {
            abort_unless($request->user()?->id === $user->id, 403);
        }

        $threadsQuery = Post::published()->visibleTo($request->user())
            ->with(['author', 'media', 'repostOf.author', 'repostOf.media', 'tags'])
            ->latest('published_at');

        if ($tab === 'replies') {
            $threads = $threadsQuery->where('user_id', $user->id)
                ->where('type', 'reply')
                ->paginate(15)
                ->withQueryString();
        } elseif ($tab === 'reposts') {
            $threads = $threadsQuery->where('user_id', $user->id)
                ->where('type', 'repost')
                ->paginate(15)
                ->withQueryString();
        } elseif ($tab === 'likes') {
            $threads = $user->likedPosts()
                ->published()->visibleTo($request->user())
                ->with(['author', 'media', 'repostOf.author', 'repostOf.media', 'tags'])
                ->latest('post_likes.created_at')
                ->paginate(15)
                ->withQueryString();
        } else {
            // Main threads (original posts + quotes)
            $threads = $threadsQuery->where('user_id', $user->id)
                ->where(function ($q): void {
                    $q->whereNull('type')
                        ->orWhereIn('type', ['post', 'quote', 'repost']);
                })
                ->whereNull('parent_id')
                ->paginate(15)
                ->withQueryString();
        }

        $followersCount = $user->followers()->count();
        $followingCount = $user->following()->count();
        $threadsCount = Post::published()->where('user_id', $user->id)->count();
        $userInteractions = app(ThreadController::class)->batchPreloadInteractions($request->user(), $threads->items());

        return view('profile.show', compact('user', 'threads', 'tab', 'followersCount', 'followingCount', 'threadsCount', 'userInteractions'));
    }

    public function followers(Request $request, string $username): JsonResponse
    {
        return $this->connectionUsers($request, $username, 'followers');
    }

    public function following(Request $request, string $username): JsonResponse
    {
        return $this->connectionUsers($request, $username, 'following');
    }

    private function connectionUsers(Request $request, string $username, string $kind): JsonResponse
    {
        $targetUser = User::discoverableTo($request->user())->where(fn ($users) => $users->where('username', $username)->orWhere('id', ctype_digit($username) ? (int) $username : 0))->firstOrFail();
        abort_unless(! $targetUser->is_locked && $targetUser->canViewPosts($request->user()), 404);
        $people = $targetUser->{$kind}()->discoverableTo($request->user())->notLocked()->latest('follows.created_at')->orderBy('users.id')->paginate(20);
        $users = $people->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name, 'username' => $user->username, 'handle' => $user->handle(), 'bio' => $user->bio, 'avatar_url' => $user->avatar_path ? route('avatar.show', $user) : null, 'profile_url' => route('profile.show', $user->username ?: $user->id), 'is_following' => $request->user()?->isFollowing($user) ?? false, 'requested' => $request->user()?->hasRequestedToFollow($user) ?? false, 'is_me' => $request->user()?->id === $user->id]);

        return response()->json(['count' => $people->total(), 'users' => $users, 'nextPageUrl' => $people->nextPageUrl()])->header('Cache-Control', 'private, no-store');
    }
}
