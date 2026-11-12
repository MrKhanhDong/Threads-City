<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'user_id', 'parent_id', 'repost_of_id', 'type', 'title', 'slug', 'excerpt',
    'content', 'content_format', 'status', 'published_at', 'is_featured', 'cover_media_id',
    'seo_title', 'meta_description', 'focus_keyword', 'canonical_url',
    'og_title', 'og_description', 'og_media_id', 'likes_count', 'replies_count', 'reposts_count',
])]
class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'archive_root_id' => 'integer',
            'is_featured' => 'boolean',
            'views_count' => 'integer',
            'lock_version' => 'integer',
            'likes_count' => 'integer',
            'replies_count' => 'integer',
            'reposts_count' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Post::class, 'parent_id')->published()->visibleTo(auth()->user())->latest();
    }

    public function repostOf(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'repost_of_id');
    }

    public function reposts(): HasMany
    {
        return $this->hasMany(Post::class, 'repost_of_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_media_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'post_category');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'post_media');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_likes')->withPivot('created_at');
    }

    public function bookmarkers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'bookmarks')->withPivot('created_at');
    }

    public function scopeWithThreadContext(Builder $query, ?User $viewer = null): Builder
    {
        return $query->threadInteractions($viewer)->with(['author', 'media', 'cover', 'categories', 'tags', 'repostOf' => fn (BelongsTo $original): BelongsTo => $original->published()->visibleTo($viewer)->threadInteractions($viewer)->with(['author', 'media', 'cover', 'categories'])]);
    }

    public function scopeThreadInteractions(Builder $query, ?User $viewer = null): Builder
    {
        return $query->withCount(['likers as likes_count', 'replies as replies_count', 'reposts as reposts_count' => fn (Builder $reposts): Builder => $reposts->published()->visibleTo($viewer)])
            ->withExists([
                'likers as viewer_liked' => fn (Builder $users): Builder => $users->whereKey($viewer?->id ?? 0),
                'bookmarkers as viewer_bookmarked' => fn (Builder $users): Builder => $users->whereKey($viewer?->id ?? 0),
                'reposts as viewer_reposted' => fn (Builder $posts): Builder => $posts->published()->visibleTo($viewer)->where('user_id', $viewer?->id ?? 0)->where('type', 'repost'),
            ]);
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->likers()->where('user_id', $user->id)->exists();
    }

    public function isBookmarkedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return DB::table('bookmarks')->where('post_id', $this->id)->where('user_id', $user->id)->exists();
    }

    public function isRepostedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return static::where('user_id', $user->id)->where('repost_of_id', $this->id)->where('type', 'repost')->exists();
    }

    public function dailyViews(): HasMany
    {
        return $this->hasMany(PostDailyView::class);
    }

    public function redirects(): HasMany
    {
        return $this->hasMany(PostRedirect::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->whereNull('archived_at')->whereIn('status', ['published', 'scheduled'])->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeVisibleTo(Builder $query, ?User $viewer, bool $hideMuted = false): Builder
    {
        $authors = User::contentVisibleTo($viewer)->select('users.id');
        if ($viewer && $hideMuted) {
            $authors->whereNotIn('users.id', $viewer->mutedUsers()->select('users.id'));
        }
        $hidden = DB::table('posts')->select('id')->where(function (\Illuminate\Database\Query\Builder $posts) use ($authors): void {
            $posts->whereNotIn('user_id', $authors)
                ->orWhereNotNull('deleted_at')->orWhereNotNull('archived_at')
                ->orWhereNotIn('status', ['published', 'scheduled'])->orWhereNull('published_at')->orWhere('published_at', '>', now());
        });
        $sql = 'WITH RECURSIVE hidden_posts AS ('.$hidden->toSql().' UNION DISTINCT '
            .'SELECT children.id FROM posts children INNER JOIN hidden_posts ON children.parent_id = hidden_posts.id OR children.repost_of_id = hidden_posts.id) '
            .'SELECT id FROM hidden_posts';

        return $query->whereNotIn($query->qualifyColumn('id'), DB::query()->fromRaw('('.$sql.') AS restricted_posts', $hidden->getBindings())->select('id'));
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->content)) / 200));
    }

    public function publicUrl(): string
    {
        return $this->slug ? route('posts.show', $this->slug) : route('threads.show', $this->id);
    }

    public function isMutedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return DB::table('muted_posts')->where('post_id', $this->id)->where('user_id', $user->id)->exists();
    }

    public function isHiddenBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return DB::table('hidden_posts')->where('post_id', $this->id)->where('user_id', $user->id)->exists();
    }
}
