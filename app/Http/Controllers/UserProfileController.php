<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
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
