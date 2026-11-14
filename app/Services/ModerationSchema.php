<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;

class ModerationSchema
{
    private ?bool $hasLockExpiry = null;

    private ?bool $isReady = null;

    public function hasLockExpiry(): bool
    {
        return $this->hasLockExpiry ??= Schema::hasColumn('users', 'lock_expires_at');
    }

    public function isReady(): bool
    {
        return $this->isReady ??= $this->hasLockExpiry()
            && Schema::hasColumn('users', 'lock_reason')
            && Schema::hasColumn('posts', 'moderation_hidden_at')
            && Schema::hasTable('moderation_logs');
    }
}
