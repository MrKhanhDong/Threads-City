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
            abort_if($post->author->is_private, 403, 'Không thể đăng lại bài viết của tài khoản riêng tư.');
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
        [$following, $requested] = DB::transaction(function () use ($request, $user): array {
            $lockedUsers = User::whereKey([$request->user()->id, $user->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $currentUser = $lockedUsers->get($request->user()->id);
            $target = $lockedUsers->get($user->id);
            abort_unless($currentUser && $target && ! $target->is_locked && ! $currentUser->isBlockedWith($target), 404);
            $pending = DB::table('follow_requests')->where('requester_id', $currentUser->id)->where('target_id', $target->id);
            if ($currentUser->isFollowing($target)) {
                $currentUser->following()->detach($target->id);
                $pending->delete();

                return [false, false];
            }
            if ((clone $pending)->exists()) {
                $pending->delete();

                return [false, false];
            }
            if ($target->is_private) {
                DB::table('follow_requests')->insertOrIgnore(['requester_id' => $currentUser->id, 'target_id' => $target->id, 'created_at' => now()]);

                return [false, true];
            }
            $currentUser->following()->syncWithoutDetaching([$target->id => ['created_at' => now()]]);

            return [true, false];
        });

        return $request->expectsJson()
            ? response()->json(['success' => true, 'following' => $following, 'requested' => $requested, 'follow_label' => $requested ? 'Đã gửi yêu cầu' : ($following ? 'Đang theo dõi' : ($user->is_private ? 'Yêu cầu theo dõi' : 'Theo dõi')), 'followers_count' => $user->followers()->count()])
            : back()->with('success', $requested ? 'Đã gửi yêu cầu theo dõi.' : ($following ? 'Đã theo dõi '.$user->handle() : 'Đã hủy theo dõi hoặc yêu cầu đang chờ.'));
    }

    private function lockPublished(Post $thread): Post
    {
        $post = Post::published()->visibleTo(auth()->user())->lockForUpdate()->findOrFail($thread->id);
        if ($post->parent_id) {
            abort_unless(Post::published()->whereKey($post->parent_id)->exists(), 404);
        }

        return $post;
    }
}
