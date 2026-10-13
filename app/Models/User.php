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

#[Fillable(['name', 'email', 'password', 'bio', 'avatar_path'])]
#[Hidden(['password', 'remember_token', 'google_id'])]
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed', 'is_locked' => 'boolean'];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
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
}
