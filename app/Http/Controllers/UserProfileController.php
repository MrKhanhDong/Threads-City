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
        $user = User::where('username', $username)
            ->orWhere('id', $username)
            ->firstOrFail();

        $tab = $request->query('tab', 'threads');

        $threadsQuery = Post::published()
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
                ->published()
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

        return view('profile.show', compact('user', 'threads', 'tab', 'followersCount', 'followingCount', 'threadsCount'));
    }
}
