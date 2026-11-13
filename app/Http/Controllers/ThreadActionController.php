<?php

namespace App\Http\Controllers;

use App\Models\CustomFeed;
use App\Models\Post;
use App\Models\PostReport;
use App\Models\User;
use App\Notifications\ThreadInteractionNotification;
use Illuminate\Contracts\View\View;
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

        if ($liked && $thread->user_id !== $user->id) {
            $thread->author?->notify(new ThreadInteractionNotification([
                'action' => 'like',
                'actor_id' => $user->id,
                'actor_name' => $user->name,
                'actor_handle' => $user->username ? '@'.$user->username : '@'.Str::slug($user->name, '_'),
                'actor_avatar' => $user->avatar_path ? route('avatar.show', $user->id) : null,
                'thread_id' => $thread->id,
                'content_snippet' => Str::limit(strip_tags($thread->content), 80),
                'url' => route('threads.show', $thread->id),
            ]));
        }

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
        abort_if($thread->author->is_private, 403, 'Không thể đăng lại bài viết của tài khoản riêng tư.');
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

        if ($reposted && $thread->user_id !== $user->id) {
            $thread->author?->notify(new ThreadInteractionNotification([
                'action' => 'repost',
                'actor_id' => $user->id,
                'actor_name' => $user->name,
                'actor_handle' => $user->username ? '@'.$user->username : '@'.Str::slug($user->name, '_'),
                'actor_avatar' => $user->avatar_path ? route('avatar.show', $user->id) : null,
                'thread_id' => $thread->id,
                'content_snippet' => Str::limit(strip_tags($thread->content), 80),
                'url' => route('threads.show', $thread->id),
            ]));
        }

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

        if ($following) {
            $user->notify(new ThreadInteractionNotification(['action' => 'follow', 'actor_id' => $request->user()->id, 'actor_name' => $request->user()->name, 'actor_handle' => $request->user()->handle(), 'actor_avatar' => $request->user()->avatar_path ? route('avatar.show', $request->user()) : null, 'thread_id' => null, 'content_snippet' => null, 'url' => route('profile.show', $request->user()->username ?: $request->user()->id)]));
        }

        return $request->expectsJson()
            ? response()->json(['success' => true, 'following' => $following, 'requested' => $requested, 'follow_label' => $requested ? 'Đã gửi yêu cầu' : ($following ? 'Đang theo dõi' : ($user->is_private ? 'Yêu cầu theo dõi' : 'Theo dõi')), 'followers_count' => $user->followers()->count(), 'target_id' => $user->id, 'viewer_id' => $request->user()->id, 'viewer_following_count' => $request->user()->following()->count()])
            : back()->with('success', $requested ? 'Đã gửi yêu cầu theo dõi.' : ($following ? 'Đã theo dõi '.$user->handle() : 'Đã hủy theo dõi hoặc yêu cầu đang chờ.'));
    }

    public function notInterested(Request $request, Post $thread): JsonResponse
    {
        $user = $request->user();

        DB::table('hidden_posts')->updateOrInsert(
            ['post_id' => $thread->id, 'user_id' => $user->id],
            ['created_at' => now()]
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã ẩn bài viết. Bạn sẽ ít thấy bài viết tương tự hơn.',
        ]);
    }

    public function toggleMute(Request $request, Post $thread): JsonResponse
    {
        $user = $request->user();

        $muted = DB::transaction(function () use ($user, $thread): bool {
            $exists = DB::table('muted_posts')
                ->where('post_id', $thread->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($exists) {
                DB::table('muted_posts')
                    ->where('post_id', $thread->id)
                    ->where('user_id', $user->id)
                    ->delete();

                return false;
            }

            DB::table('muted_posts')->insert([
                'post_id' => $thread->id,
                'user_id' => $user->id,
                'created_at' => now(),
            ]);

            return true;
        });

        return response()->json([
            'success' => true,
            'muted' => $muted,
            'message' => $muted ? 'Đã tắt thông báo cho bài viết này.' : 'Đã bật lại thông báo cho bài viết.',
        ]);
    }

    public function report(Request $request, Post $thread): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'in:spam,hate_speech,false_info,sensitive,other'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        PostReport::updateOrCreate(
            ['post_id' => $thread->id, 'user_id' => $user->id],
            [
                'reason' => $validated['reason'],
                'notes' => $validated['notes'] ?? null,
                'resolved' => false,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Cảm ơn bạn đã báo cáo. Chúng tôi sẽ xem xét bài viết này để bảo vệ cộng đồng.',
        ]);
    }

    public function toggleBlock(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();
        if ($currentUser->id === $user->id) {
            return response()->json(['success' => false, 'message' => 'Không thể chặn chính mình.'], 422);
        }

        $blocked = DB::transaction(function () use ($currentUser, $user): bool {
            User::whereKey([$currentUser->id, $user->id])->orderBy('id')->lockForUpdate()->get();
            $exists = DB::table('user_blocks')
                ->where('user_id', $currentUser->id)
                ->where('blocked_user_id', $user->id)
                ->exists();

            if ($exists) {
                DB::table('user_blocks')
                    ->where('user_id', $currentUser->id)
                    ->where('blocked_user_id', $user->id)
                    ->delete();

                DB::table('user_safety_blocks')->where('blocker_id', $currentUser->id)->where('blocked_id', $user->id)->delete();

                return false;
            }

            DB::table('user_safety_blocks')->insertOrIgnore(['blocker_id' => $currentUser->id, 'blocked_id' => $user->id, 'created_at' => now()]);
            DB::table('follow_requests')->where(fn ($pairs) => $pairs->where('requester_id', $currentUser->id)->where('target_id', $user->id))->orWhere(fn ($pairs) => $pairs->where('requester_id', $user->id)->where('target_id', $currentUser->id))->delete();
            DB::table('user_blocks')->insert([
                'user_id' => $currentUser->id,
                'blocked_user_id' => $user->id,
                'created_at' => now(),
            ]);

            $currentUser->following()->detach($user->id);
            $user->following()->detach($currentUser->id);

            return true;
        });

        return response()->json([
            'success' => true,
            'blocked' => $blocked,
            'message' => $blocked ? "Đã chặn tài khoản {$user->name}." : "Đã bỏ chặn tài khoản {$user->name}.",
        ]);
    }

    public function toggleRestrict(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();
        if ($currentUser->id === $user->id) {
            return response()->json(['success' => false, 'message' => 'Không thể hạn chế chính mình.'], 422);
        }
        $restricted = DB::transaction(function () use ($currentUser, $user): bool {
            $exists = DB::table('user_restrictions')
                ->where('user_id', $currentUser->id)
                ->where('restricted_user_id', $user->id)
                ->exists();

            if ($exists) {
                DB::table('user_restrictions')
                    ->where('user_id', $currentUser->id)
                    ->where('restricted_user_id', $user->id)
                    ->delete();

                return false;
            }

            DB::table('user_restrictions')->insert([
                'user_id' => $currentUser->id,
                'restricted_user_id' => $user->id,
                'created_at' => now(),
            ]);

            return true;
        });

        return response()->json([
            'success' => true,
            'restricted' => $restricted,
            'message' => $restricted ? "Đã đưa {$user->name} vào danh sách hạn chế." : "Đã bỏ hạn chế đối với {$user->name}.",
        ]);
    }

    public function addToCustomFeed(Request $request, Post $thread): JsonResponse
    {
        $user = $request->user();
        $feedId = $request->input('feed_id');

        if ($feedId) {
            $feed = CustomFeed::where('user_id', $user->id)->findOrFail($feedId);
        } else {
            $feed = CustomFeed::firstOrCreate(
                ['user_id' => $user->id, 'slug' => 'bang-feed-cua-toi'],
                ['name' => 'Bảng feed của tôi', 'description' => 'Bảng feed tổng hợp tùy chỉnh']
            );
        }

        $feed->posts()->syncWithoutDetaching([$thread->id => ['created_at' => now()]]);

        return response()->json([
            'success' => true,
            'feed_id' => $feed->id,
            'feed_name' => $feed->name,
            'message' => "Đã thêm bài viết vào \"{$feed->name}\".",
        ]);
    }

    public function getCustomFeeds(Request $request): JsonResponse
    {
        $feeds = $request->user()->customFeeds()->latest()->get(['id', 'name', 'slug']);

        return response()->json([
            'success' => true,
            'feeds' => $feeds,
        ]);
    }

    public function embed(Request $request, Post $thread): View
    {
        abort_unless($thread->status === 'published' && $thread->published_at <= now(), 404);

        $thread->load(['author', 'media', 'repostOf.author', 'repostOf.media', 'tags']);

        return view('threads.embed', compact('thread'));
    }
}
