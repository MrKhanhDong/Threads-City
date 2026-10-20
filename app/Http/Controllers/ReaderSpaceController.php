<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\CommentThreads;
use App\Services\ThreadInsights;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReaderSpaceController extends Controller
{
    public function liked(Request $request): View
    {
        $threads = $request->user()->likedPosts()->published()->withThreadContext($request->user())
            ->where(fn (Builder $posts): Builder => $posts->whereNull('repost_of_id')->orWhereHas('repostOf', fn (Builder $original): Builder => $original->published()))
            ->latest('post_likes.created_at')->latest('posts.id')->paginate(15);

        return view('threads.liked', compact('threads'));
    }

    public function insights(Request $request, ThreadInsights $statistics): View
    {
        $filters = $request->validate(['days' => 'nullable|in:7,30,90', 'metric' => 'nullable|in:views,likes,followers,interactions']);
        $days = (int) ($filters['days'] ?? 7);
        $metric = $filters['metric'] ?? 'views';
        $insights = $statistics->summarize($request->user(), $days);
        $threads = $statistics->postsInPeriod($request->user(), $insights['start'], $insights['end'])
            ->orderByDesc('period_views')->orderByDesc('period_likes')->latest('published_at')->latest('id')->paginate(10)->withQueryString();

        return view('threads.insights', compact('insights', 'threads', 'metric'));
    }

    public function activity(Request $request, CommentThreads $commentThreads): View
    {
        $filters = $request->validate(['type' => 'nullable|in:all,likes,replies,follows,reposts']);
        $type = $filters['type'] ?? 'all';
        $viewerId = $request->user()->id;
        $ownedPosts = Post::published()->where('user_id', $viewerId)->select('id');
        $likes = DB::table('post_likes as interactions')->whereIn('interactions.post_id', clone $ownedPosts)->where('interactions.user_id', '!=', $viewerId)
            ->selectRaw("'likes' as kind, interactions.user_id as actor_id, interactions.post_id as post_id, NULL as comment_id, interactions.created_at as occurred_at, CONCAT('like-', interactions.post_id, '-', interactions.user_id) as event_key");
        $follows = DB::table('follows')->where('following_id', $viewerId)
            ->selectRaw("'follows' as kind, follower_id as actor_id, NULL as post_id, NULL as comment_id, created_at as occurred_at, CONCAT('follow-', follower_id) as event_key");
        $replies = DB::table('posts')->whereIn('id', Post::published()->select('id'))->whereIn('parent_id', clone $ownedPosts)->where('user_id', '!=', $viewerId)
            ->selectRaw("'replies' as kind, user_id as actor_id, id as post_id, NULL as comment_id, created_at as occurred_at, CONCAT('reply-', id) as event_key");
        $reposts = DB::table('posts')->whereIn('id', Post::published()->select('id'))->whereIn('repost_of_id', clone $ownedPosts)->whereIn('type', ['repost', 'quote'])->where('user_id', '!=', $viewerId)
            ->selectRaw("'reposts' as kind, user_id as actor_id, id as post_id, NULL as comment_id, created_at as occurred_at, CONCAT('repost-', id) as event_key");
        $comments = DB::table('comments')->whereIn('id', $commentThreads->visible()->select('id'))->where('user_id', '!=', $viewerId)
            ->where(fn (\Illuminate\Database\Query\Builder $comments): \Illuminate\Database\Query\Builder => $comments->whereIn('post_id', clone $ownedPosts)->orWhereIn('parent_id', Comment::where('user_id', $viewerId)->select('id')))
            ->selectRaw("'replies' as kind, user_id as actor_id, post_id, id as comment_id, created_at as occurred_at, CONCAT('comment-', id) as event_key");
        $events = DB::query()->fromSub($likes->unionAll($follows)->unionAll($replies)->unionAll($reposts)->unionAll($comments), 'events');
        if ($type !== 'all') {
            $events->where('kind', $type);
        }
        $activities = $events->orderByDesc('occurred_at')->orderByDesc('event_key')->paginate(20)->withQueryString();
        $actors = User::whereIn('id', $activities->pluck('actor_id'))->get()->keyBy('id');
        $posts = Post::published()->whereIn('id', $activities->pluck('post_id')->filter())->get()->keyBy('id');
        $comments = Comment::with('post')->whereIn('id', $activities->pluck('comment_id')->filter())->get()->keyBy('id');

        return view('threads.activity', compact('activities', 'actors', 'posts', 'comments', 'type', 'commentThreads'));
    }

    public function archives(Request $request): View
    {
        $threads = $request->user()->posts()->whereNotNull('archived_at')->whereNull('parent_id')->whereColumn('archive_root_id', 'id')
            ->with(['media', 'cover'])->latest('archived_at')->latest('id')->paginate(15);

        return view('threads.archives', compact('threads'));
    }

    public function archive(Request $request, Post $thread): RedirectResponse
    {
        abort_unless($thread->user_id === $request->user()->id, 403);
        abort_if($thread->parent_id !== null || $thread->type === 'repost', 422, 'Chỉ lưu trữ bài viết gốc của bạn.');
        DB::transaction(function () use ($thread): void {
            $root = Post::lockForUpdate()->findOrFail($thread->id);
            abort_unless($root->archived_at === null && Post::published()->whereKey($root->id)->exists(), 404);
            $branch = [$root->id];
            $descendants = [];
            while ($branch !== []) {
                $descendants = [...$descendants, ...$branch];
                $branch = Post::withTrashed()->whereIn('parent_id', $branch)->lockForUpdate()->pluck('id')->all();
            }
            Post::withTrashed()->whereIn('id', $descendants)->whereNull('archived_at')->update(['archived_at' => now(), 'archive_root_id' => $root->id]);
        });

        return redirect()->route('archives.index')->with('success', 'Đã lưu trữ bài viết và cuộc trò chuyện. Bạn có thể khôi phục bất cứ lúc nào.');
    }

    public function restore(Request $request, Post $thread): RedirectResponse
    {
        abort_unless($thread->user_id === $request->user()->id, 403);
        DB::transaction(function () use ($thread): void {
            $root = Post::lockForUpdate()->findOrFail($thread->id);
            abort_unless($root->archived_at !== null && $root->archive_root_id === $root->id, 404);
            Post::withTrashed()->where('archive_root_id', $root->id)->update(['archived_at' => null, 'archive_root_id' => null]);
        });

        return redirect()->route('archives.index')->with('success', 'Đã khôi phục bài viết và cuộc trò chuyện.');
    }
}
