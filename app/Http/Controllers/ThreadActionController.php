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
    }

    public function bookmark(Request $request, Post $thread): JsonResponse|RedirectResponse
    {
        $bookmarked = DB::transaction(function () use ($request, $thread): bool {
            $post = $this->lockPublished($thread);
            $result = $post->bookmarkers()->toggle([$request->user()->id => ['created_at' => now()]]);

            return count($result['attached']) > 0;
        });

        return $request->expectsJson()
            ? response()->json(['success' => true, 'bookmarked' => $bookmarked])
            : back()->with('success', $bookmarked ? 'Đã lưu bài viết.' : 'Đã bỏ lưu bài viết.');
    }

    public function repost(Request $request, Post $thread): JsonResponse|RedirectResponse
    {
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
    }

    public function follow(Request $request, User $user): JsonResponse|RedirectResponse
    {
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
    }
}
