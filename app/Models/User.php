<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable(['name', 'username', 'email', 'password', 'bio', 'avatar_path'])]
#[Hidden(['password', 'remember_token', 'google_id'])]
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /** @var array<string, bool> */
    protected $attributes = ['has_local_password' => true, 'google_profile_pending' => false];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_locked' => 'boolean', 'is_verified' => 'boolean', 'is_private' => 'boolean', 'google_profile_pending' => 'boolean', 'has_local_password' => 'boolean'];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function handle(): string
    {
        return $this->username ? '@'.$this->username : '@'.Str::slug($this->name, '_');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function bookmarks(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'bookmarks')->withPivot('created_at');
    }

    public function likedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_likes')->withPivot('created_at');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'following_id', 'follower_id')->withPivot('created_at');
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'follows', 'follower_id', 'following_id')->withPivot('created_at');
    }

    public function isFollowing(User $user): bool
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_safety_blocks', 'blocker_id', 'blocked_id')->withPivot('created_at');
    }

    public function mutedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_mutes', 'user_id', 'muted_user_id')->withPivot('created_at');
    }

    public function isBlockedWith(User $user): bool
    {
        return DB::table('user_blocks')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('user_id', $this->id)->where('blocked_user_id', $user->id))->orWhere(fn (QueryBuilder $query): QueryBuilder => $query->where('user_id', $user->id)->where('blocked_user_id', $this->id))->exists() || DB::table('user_safety_blocks')->where(fn (QueryBuilder $query): QueryBuilder => $query->where('blocker_id', $this->id)->where('blocked_id', $user->id))
            ->orWhere(fn (QueryBuilder $query): QueryBuilder => $query->where('blocker_id', $user->id)->where('blocked_id', $this->id))->exists();
    }

    public function hasRequestedToFollow(User $user): bool
    {
        return DB::table('follow_requests')->where('requester_id', $this->id)->where('target_id', $user->id)->exists();
    }

    public function canViewPosts(?User $viewer): bool
    {
        return (! $viewer || ! $this->isBlockedWith($viewer)) && (! $this->is_private || $viewer?->id === $this->id || ($viewer?->isFollowing($this) ?? false));
    }

    public function scopeDiscoverableTo(Builder $query, ?User $viewer): Builder
    {
        if ($viewer) {
            $query->whereNotIn($query->qualifyColumn('id'), DB::table('user_blocks')->where('user_id', $viewer->id)->select('blocked_user_id'))->whereNotIn($query->qualifyColumn('id'), DB::table('user_blocks')->where('blocked_user_id', $viewer->id)->select('user_id'))->whereNotIn($query->qualifyColumn('id'), DB::table('user_safety_blocks')->where('blocker_id', $viewer->id)->select('blocked_id'))
                ->whereNotIn($query->qualifyColumn('id'), DB::table('user_safety_blocks')->where('blocked_id', $viewer->id)->select('blocker_id'));
        }

        return $query;
    }

    public function scopeContentVisibleTo(Builder $query, ?User $viewer): Builder
    {
        return $query->discoverableTo($viewer)->where(function (Builder $authors) use ($viewer): void {
            $authors->where('is_private', false);
            if ($viewer) {
                $authors->orWhereKey($viewer->id)->orWhereHas('followers', fn (Builder $followers): Builder => $followers->whereKey($viewer->id));
            }
        });
    }

    public function isBlocking(User|int $user): bool
    {
        $targetId = $user instanceof User ? $user->id : (int) $user;

        return DB::table('user_blocks')->where('user_id', $this->id)->where('blocked_user_id', $targetId)->exists() || $this->blockedUsers()->where('blocked_id', $targetId)->exists();
    }

    public function restrictedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_restrictions', 'user_id', 'restricted_user_id')->withPivot('created_at');
    }

    public function isRestricting(User|int $user): bool
    {
        $targetId = $user instanceof User ? $user->id : (int) $user;

        return $this->restrictedUsers()->where('restricted_user_id', $targetId)->exists();
    }

    public function hiddenPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'hidden_posts')->withPivot('created_at');
    }

    public function mutedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'muted_posts')->withPivot('created_at');
    }

    public function customFeeds(): HasMany
    {
        return $this->hasMany(CustomFeed::class);
    }
}
