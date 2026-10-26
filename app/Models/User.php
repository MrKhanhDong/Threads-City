<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_locked' => 'boolean', 'google_profile_pending' => 'boolean', 'has_local_password' => 'boolean'];
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
        return $this->belongsToMany(User::class, 'user_blocks', 'user_id', 'blocked_user_id')->withPivot('created_at');
    }

    public function isBlocking(User|int $user): bool
    {
        $targetId = $user instanceof User ? $user->id : (int) $user;

        return $this->blockedUsers()->where('blocked_user_id', $targetId)->exists();
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
