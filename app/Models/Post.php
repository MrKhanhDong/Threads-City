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
    'content', 'status', 'published_at', 'is_featured', 'cover_media_id',
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
        return $this->hasMany(Post::class, 'parent_id')->published()->latest();
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
        return $q->whereIn('status', ['published', 'scheduled'])->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function readingMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->content)) / 200));
    }

    public function publicUrl(): string
    {
        return $this->slug ? route('posts.show', $this->slug) : route('threads.show', $this->id);
    }
}
