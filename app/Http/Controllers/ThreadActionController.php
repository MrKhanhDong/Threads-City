<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ThreadActionController extends Controller
{
    public function like(Request $request, Post $thread): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $liked = DB::transaction(function () use ($user, $thread): bool {
            $isLiked = DB::table('post_likes')
                ->where('post_id', $thread->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($isLiked) {
                DB::table('post_likes')
                    ->where('post_id', $thread->id)
                    ->where('user_id', $user->id)
                    ->delete();
                $thread->decrement('likes_count');

                return false;
            }

            DB::table('post_likes')->insert([
                'post_id' => $thread->id,
                'user_id' => $user->id,
                'created_at' => now(),
            ]);
            $thread->increment('likes_count');

            return true;
        });

        $likesCount = max(0, (int) $thread->fresh()->likes_count);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'liked' => $liked,
                'likes_count' => $likesCount,
            ]);
        }

        return back()->with('success', $liked ? 'Đã thích bài viết.' : 'Đã bỏ thích.');
    }

    public function bookmark(Request $request, Post $thread): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $bookmarked = DB::transaction(function () use ($user, $thread): bool {
            $exists = DB::table('bookmarks')
                ->where('post_id', $thread->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($exists) {
                DB::table('bookmarks')
                    ->where('post_id', $thread->id)
                    ->where('user_id', $user->id)
                    ->delete();

                return false;
            }

            DB::table('bookmarks')->insert([
                'post_id' => $thread->id,
                'user_id' => $user->id,
                'created_at' => now(),
            ]);

            return true;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'bookmarked' => $bookmarked,
            ]);
        }

        return back()->with('success', $bookmarked ? 'Đã lưu vào danh sách của bạn.' : 'Đã bỏ lưu.');
    }

    public function repost(Request $request, Post $thread): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $reposted = DB::transaction(function () use ($user, $thread): bool {
            $existing = Post::where('user_id', $user->id)
                ->where('repost_of_id', $thread->id)
                ->where('type', 'repost')
                ->first();

            if ($existing) {
                $existing->delete();
                $thread->decrement('reposts_count');

                return false;
            }

            Post::create([
                'user_id' => $user->id,
                'repost_of_id' => $thread->id,
                'type' => 'repost',
                'title' => 'Repost',
                'slug' => 'repost-'.Str::lower(Str::random(10)).'-'.time(),
                'content' => '',
                'status' => 'published',
                'published_at' => now(),
            ]);
            $thread->increment('reposts_count');

            return true;
        });

        $repostsCount = max(0, (int) $thread->fresh()->reposts_count);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'reposted' => $reposted,
                'reposts_count' => $repostsCount,
            ]);
        }

        return back()->with('success', $reposted ? 'Đã đăng lại bài viết.' : 'Đã hủy đăng lại.');
    }

    public function follow(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $currentUser = $request->user();
        if ($currentUser->id === $user->id) {
            abort(422, 'Bạn không thể tự theo dõi chính mình.');
        }

        $following = DB::transaction(function () use ($currentUser, $user): bool {
            $isFollowing = $currentUser->following()->where('following_id', $user->id)->exists();

            if ($isFollowing) {
                $currentUser->following()->detach($user->id);

                return false;
            }

            $currentUser->following()->attach($user->id, ['created_at' => now()]);

            return true;
        });

        $followersCount = $user->followers()->count();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'following' => $following,
                'followers_count' => $followersCount,
            ]);
        }

        return back()->with('success', $following ? 'Đã theo dõi @'.$user->username : 'Đã hủy theo dõi.');
    }
}
