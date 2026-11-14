<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin() && ! $user->is_locked;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->isAdmin() && ! $user->is_locked && $post->user_id === $user->id && ! $post->moderation_hidden_at;
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }
}
