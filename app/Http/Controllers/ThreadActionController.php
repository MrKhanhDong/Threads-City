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
<<<<<<< HEAD
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
=======
        [$liked, $count] = DB::transaction(function () use ($request, $thread): array {
            $post = $this->lockPublished($thread);
            $result = $post->likers()->toggle([$request->user()->id => ['created_at' => now()]]);
            $count = $post->likers()->count();
            $post->update(['likes_count' => $count]);

            return [count($result['attached']) > 0, $count];
        });

        return $request->expectsJson()
            ? response()->json(['success' => true, 'liked' => $liked, 'likes_count' => $count])
            : back()->with('success', $liked ? 'Đã thích bài viết.' : 'Đã bỏ thích.');
>>>>>>> Phuong
    }

    public function bookmark(Request $request, Post $thread): JsonResponse|RedirectResponse
    {
<<<<<<< HEAD
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
=======
        $bookmarked = DB::transaction(function () use ($request, $thread): bool {
            $post = $this->lockPublished($thread);
            $result = $post->bookmarkers()->toggle([$request->user()->id => ['created_at' => now()]]);

            return count($result['attached']) > 0;
        });

        return $request->expectsJson()
            ? response()->json(['success' => true, 'bookmarked' => $bookmarked])
            : back()->with('success', $bookmarked ? 'Đã lưu bài viết.' : 'Đã bỏ lưu bài viết.');
>>>>>>> Phuong
    }

    public function repost(Request $request, Post $thread): JsonResponse|RedirectResponse
    {
<<<<<<< HEAD
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
=======
        [$reposted, $count] = DB::transaction(function () use ($request, $thread): array {
            $post = $this->lockPublished($thread);
            $existing = $post->reposts()->where('user_id', $request->user()->id)->where('type', 'repost')->first();
            if ($existing) {
                $existing->delete();
            } else {
                Post::create(['user_id' => $request->user()->id, 'repost_of_id' => $post->id, 'type' => 'repost', 'title' => 'Đăng lại', 'slug' => 'thread-'.Str::uuid(), 'content' => '', 'status' => 'published', 'published_at' => now()]);
            }
            $count = $post->reposts()->published()->count();
            $post->update(['reposts_count' => $count]);

            return [! $existing, $count];
        });

        return $request->expectsJson()
            ? response()->json(['success' => true, 'reposted' => $reposted, 'reposts_count' => $count])
            : back()->with('success', $reposted ? 'Đã đăng lại bài viết.' : 'Đã hủy đăng lại.');
>>>>>>> Phuong
    }

    public function follow(Request $request, User $user): JsonResponse|RedirectResponse
    {
<<<<<<< HEAD
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
=======
        abort_if($request->user()->id === $user->id, 422, 'Bạn không thể tự theo dõi chính mình.');
        $following = DB::transaction(function () use ($request, $user): bool {
            $currentUser = User::lockForUpdate()->findOrFail($request->user()->id);
            $result = $currentUser->following()->toggle([$user->id => ['created_at' => now()]]);

            return count($result['attached']) > 0;
        });

        return $request->expectsJson()
            ? response()->json(['success' => true, 'following' => $following, 'followers_count' => $user->followers()->count()])
            : back()->with('success', $following ? 'Đã theo dõi '.$user->handle() : 'Đã hủy theo dõi.');
    }

    private function lockPublished(Post $thread): Post
    {
        $post = Post::published()->lockForUpdate()->findOrFail($thread->id);
        if ($post->parent_id) {
            abort_unless(Post::published()->whereKey($post->parent_id)->exists(), 404);
        }

        return $post;
>>>>>>> Phuong
    }
}
