<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\CommentReplied;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CommentThreads
{
    /** Only reachable comments are visible; hiding an ancestor hides its entire branch. */
    public function visible(?int $viewerId = null, ?int $postId = null, ?int $rootId = null): Builder
    {
        $viewer = $viewerId !== null ? User::find($viewerId) : auth()->user();
        $authors = User::discoverableTo($viewer)->select('users.id');
        $status = "(c.status = 'approved' OR (c.status = 'pending' AND c.user_id = ?)) AND c.user_id IN (".$authors->toSql().')';
        $statusBindings = array_merge([$viewerId ?? 0], $authors->getBindings());
        $seed = '';
        $bindings = array_merge([now()], $statusBindings);
        if ($postId !== null) {
            $seed .= ' AND c.post_id = ?';
            $bindings[] = $postId;
        }
        if ($rootId !== null) {
            $seed .= ' AND c.id = ?';
            $bindings[] = $rootId;
        }
        $bindings = array_merge($bindings, $statusBindings);
        $sql = "WITH RECURSIVE visible AS (
            SELECT c.id, c.id AS root_id, c.post_id FROM comments c
            JOIN posts p ON p.id = c.post_id
            WHERE c.parent_id IS NULL AND p.deleted_at IS NULL
              AND p.status IN ('published', 'scheduled') AND p.published_at <= ?
              AND {$status}{$seed}
            UNION DISTINCT
            SELECT c.id, v.root_id, c.post_id FROM comments c
            JOIN visible v ON c.parent_id = v.id AND c.post_id = v.post_id
            WHERE {$status}
        ) SELECT id, root_id, post_id FROM visible";

        return DB::query()->fromRaw('('.$sql.') AS visible_comments', $bindings)
            ->whereIn('post_id', Post::published()->visibleTo($viewer)->select('posts.id'));
    }

    public function root(Comment $comment, ?int $viewerId = null): ?int
    {
        $id = $this->visible($viewerId, $comment->post_id)->where('id', $comment->id)->value('root_id');

        return $id === null ? null : (int) $id;
    }

    /** @return array{replies: Collection, next: int, more: bool, earlier: bool} */
    public function batch(Comment $root, ?int $viewerId, int $after = 0, ?int $focus = null): array
    {
        $query = Comment::where('post_id', $root->post_id)->whereNotNull('parent_id')
            ->whereIn('id', $this->visible($viewerId, $root->post_id, $root->id)->select('id'));
        if ($focus !== null && (clone $query)->whereKey($focus)->exists()) {
            $after = $focus - 1;
        }
        $earlier = $after > 0 && (clone $query)->where('id', '<=', $after)->exists();
        $replies = $query->where('id', '>', $after)->with(['user', 'parent.user'])->orderBy('id')->limit(6)->get();
        $more = $replies->count() > 5;
        $replies = $replies->take(5);

        return ['replies' => $replies, 'next' => (int) ($replies->last()?->id ?? $after), 'more' => $more, 'earlier' => $earlier];
    }

    public function url(Comment $comment, ?int $viewerId = null): string
    {
        $rootId = $this->root($comment, $viewerId);
        abort_unless($rootId !== null, 404);
        $rootsBefore = $this->visible($viewerId, $comment->post_id)->whereColumn('id', 'root_id')->where('id', '>', $rootId)->count();
        $page = intdiv($rootsBefore, 10) + 1;
        $parameters = ['slug' => $comment->post->slug];
        if ($page > 1) {
            $parameters['comments_page'] = $page;
        }
        if ($comment->id !== $rootId) {
            $parameters['comment'] = $comment->id;
        }

        return route('posts.show', $parameters).'#comment-'.$comment->id;
    }

    public function notifications(User $user): MorphMany
    {
        return $user->notifications()->where('type', CommentReplied::class)
            ->whereIn('data->comment_id', $this->visible($user->id)->select('id'));
    }
}
