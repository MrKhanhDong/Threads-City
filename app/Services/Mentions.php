<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Mentioned;
use DOMDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Mentions
{
    private ?Request $currentRequest = null;

    /** @var array<int, array<int, string>> */
    private array $postLabels = [];

    /** @var array<int, User|null> */
    private array $visibleUsers = [];

    private function resetContext(): void
    {
        if ($this->currentRequest !== request()) {
            $this->currentRequest = request();
            $this->postLabels = [];
            $this->visibleUsers = [];
        }
    }

    public function visibleUser(int $id): ?User
    {
        $this->resetContext();
        if (! array_key_exists($id, $this->visibleUsers)) {
            $this->visibleUsers[$id] = User::discoverableTo(auth()->user())->where('is_locked', false)->find($id);
        }

        return $this->visibleUsers[$id];
    }

    /** @param iterable<Post> $posts */
    public function preloadPosts(iterable $posts): void
    {
        $this->resetContext();
        $ids = [];
        foreach ($posts as $post) {
            if (! array_key_exists($post->id, $this->postLabels)) {
                $ids[] = $post->id;
            }
            if ($post->repost_of_id && $post->relationLoaded('repostOf') && $post->repostOf && ! array_key_exists($post->repost_of_id, $this->postLabels)) {
                $ids[] = $post->repost_of_id;
            }
        }
        $ids = array_unique($ids);
        if ($ids === []) {
            return;
        }
        foreach ($ids as $id) {
            $this->postLabels[$id] = [];
        }
        $records = DB::table('content_mentions')->whereIn('post_id', $ids)->where('active', true)->get(['post_id', 'user_id', 'label']);
        $users = User::discoverableTo(auth()->user())->where('is_locked', false)->whereKey($records->pluck('user_id')->unique())->get()->keyBy('id');
        foreach ($records as $record) {
            $this->postLabels[$record->post_id][$record->user_id] = $record->label;
            $this->visibleUsers[$record->user_id] = $users->get($record->user_id);
        }
    }

    public function candidates(User $actor): Builder
    {
        return User::discoverableTo($actor)->where('is_locked', false)->whereNotNull('username')
            ->where(function (Builder $users) use ($actor): void {
                $users->whereKey($actor->id)->orWhere('mention_policy', 'everyone')
                    ->orWhere(function (Builder $following) use ($actor): void {
                        $following->where('mention_policy', 'following')
                            ->whereHas('following', fn (Builder $people): Builder => $people->whereKey($actor->id));
                    });
            });
    }

    public function userIdFromUrl(string $value): ?int
    {
        $base = parse_url(route('mentions.profile', 1));
        $parts = parse_url($value);
        if ($parts === false || isset($parts['query']) || isset($parts['fragment']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }
        if (isset($parts['host']) && (strtolower($parts['host']) !== strtolower($base['host']) || ($parts['scheme'] ?? '') !== $base['scheme'] || ($parts['port'] ?? null) !== ($base['port'] ?? null))) {
            return null;
        }
        $pattern = '~^'.preg_quote(dirname($base['path']), '~').'/([1-9][0-9]*)$~';

        return preg_match($pattern, $parts['path'] ?? '', $matches) ? (int) $matches[1] : null;
    }

    /** @return array<int, string> */
    public function fromHtml(string $html): array
    {
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $selected = [];
        foreach ($document->getElementsByTagName('a') as $link) {
            $id = $this->userIdFromUrl($link->getAttribute('href'));
            if ($id !== null && str_starts_with($link->textContent, '@')) {
                $selected[$id] = $link->textContent;
            }
        }

        return $selected;
    }

    /** @param array<int, int|string> $ids
     * @return array<int, string>
     */
    public function fromComment(Comment $comment, array $ids): array
    {
        return $this->fromPlain($comment, $ids);
    }

    /** @param array<int, int|string> $ids
     * @return array<int, string>
     */
    public function fromPlain(Post|Comment $comment, array $ids): array
    {
        $labels = DB::table('content_mentions')->where($comment instanceof Post ? 'post_id' : 'comment_id', $comment->id)->pluck('label', 'user_id');
        $users = User::whereKey($ids)->get()->keyBy('id');
        $selected = [];
        foreach (array_unique($ids) as $id) {
            $user = $users->get($id);
            if (! $user) {
                throw ValidationException::withMessages(['body' => 'Tài khoản được nhắc đến không còn tồn tại.']);
            }
            $possible = array_filter([$user->username ? $user->handle() : null, $labels->get($id)]);
            foreach ($possible as $label) {
                if (preg_match('~(?<![\pL\pN_@-])'.preg_quote($label, '~').'(?![\pL\pN_-])~u', $comment instanceof Post ? $comment->content : $comment->body)) {
                    $selected[(int) $id] = $label;
                    break;
                }
            }
        }
        if (count(array_unique($selected)) !== count($selected)) {
            throw ValidationException::withMessages(['body' => 'Có nhắc đến trùng tên sau khi tài khoản đổi username. Hãy xóa nhắc đến cũ và chọn lại đúng người từ gợi ý.']);
        }

        return $selected;
    }

    /** @param array<int, string> $selected */
    public function sync(Post|Comment $source, User $actor, array $selected): void
    {
        $this->resetContext();
        if ($source instanceof Post) {
            unset($this->postLabels[$source->id]);
        }
        if (count(array_diff(array_keys($selected), [$actor->id])) > 5) {
            throw ValidationException::withMessages([$source instanceof Post ? 'content' : 'body' => 'Mỗi bài viết hoặc bình luận chỉ được nhắc tối đa 5 người khác nhau ngoài bản thân.']);
        }
        $field = $source instanceof Post ? 'post_id' : 'comment_id';
        $users = $this->candidates($actor)->whereKey(array_keys($selected))->get()->keyBy('id');
        if ($users->count() !== count($selected)) {
            throw ValidationException::withMessages([$source instanceof Post ? 'content' : 'body' => 'Có tài khoản không cho phép bạn nhắc đến, đã bị khóa, bị xóa hoặc đã chặn nhau. Hãy gỡ nhắc đến tài khoản đó.']);
        }
        $history = DB::table('content_mentions')->where($field, $source->id)->get()->keyBy('user_id');
        DB::table('content_mentions')->where($field, $source->id)->update(['active' => false]);
        foreach ($selected as $userId => $label) {
            $recipient = $users->get($userId);
            if ($source instanceof Post) {
                $label = $recipient->handle();
            }
            if ($history->has($userId)) {
                DB::table('content_mentions')->where('id', $history->get($userId)->id)->update(['active' => true, 'label' => $label]);

                continue;
            }
            $mentionId = DB::table('content_mentions')->insertGetId([$field => $source->id, 'user_id' => $userId, 'label' => $label, 'active' => true, 'created_at' => now()]);
            if ($actor->id === $userId || $recipient->mutedUsers()->whereKey($actor->id)->exists() || ! $this->visibleTo($recipient)->where('id', $mentionId)->exists()) {
                continue;
            }
            $key = 'mention-notifications:'.$actor->id;
            if (RateLimiter::tooManyAttempts($key, 20)) {
                throw ValidationException::withMessages([$source instanceof Post ? 'content' : 'body' => 'Bạn đang nhắc đến quá nhiều người. Hãy đợi một phút rồi thử lại.']);
            }
            RateLimiter::hit($key, 60);
            $recipient->notify(new Mentioned($mentionId));
        }
    }

    public function visibleTo(User $viewer): QueryBuilder
    {
        return DB::table('content_mentions')->where('user_id', $viewer->id)->where('active', true)
            ->where(function (QueryBuilder $mentions) use ($viewer): void {
                $mentions->whereIn('post_id', Post::published()->visibleTo($viewer)->whereHas('author', fn (Builder $author): Builder => $author->where('is_locked', false))->select('posts.id'))
                    ->orWhereIn('comment_id', app(CommentThreads::class)->visible($viewer->id)->whereIn('id', Comment::whereHas('user', fn (Builder $users): Builder => $users->where('is_locked', false))->select('id'))->select('id'));
            });
    }

    /** @return array<int, string> */
    public function commentSelections(Comment $comment): array
    {
        return DB::table('content_mentions')->where('comment_id', $comment->id)->where('active', true)->pluck('label', 'user_id')->all();
    }

    public function commentHtml(Post|Comment $comment): string
    {
        if ($comment instanceof Post && $comment->content_format === 'html') {
            return app(ThreadText::class)->html($comment->content);
        }
        if ($comment instanceof Post) {
            $this->preloadPosts([$comment]);
        }
        $labels = $comment instanceof Post ? $this->postLabels[$comment->id] : $this->commentSelections($comment);
        $body = $comment instanceof Post ? $comment->content : $comment->body;
        if ($labels === []) {
            return e($body);
        }
        $html = e($body);
        $replacements = [];
        foreach ($labels as $id => $label) {
            if ($user = $this->visibleUser((int) $id)) {
                $replacements[$label] = '<a class="stream-mention" href="'.e(route('mentions.profile', $id)).'">'.e($user->handle()).'</a>';
            }
        }
        if ($replacements === []) {
            return $html;
        }
        $pattern = '~(?<![\pL\pN_@-])('.implode('|', array_map(fn (string $label): string => preg_quote(e($label), '~'), array_keys($replacements))).')(?![\pL\pN_-])~u';

        return preg_replace_callback($pattern, fn (array $match): string => $replacements[html_entity_decode($match[1], ENT_QUOTES, 'UTF-8')], $html) ?? $html;
    }
}
