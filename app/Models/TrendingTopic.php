<?php

namespace App\Models;

use Database\Factories\TrendingTopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['tag', 'title', 'desc', 'count', 'posts_count', 'order', 'is_active'])]
class TrendingTopic extends Model
{
    /** @use HasFactory<TrendingTopicFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'posts_count' => 'integer',
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getDisplayCountAttribute(): string
    {
        if (! empty($this->count)) {
            return $this->count;
        }

        if ($this->posts_count > 0) {
            if ($this->posts_count >= 1000) {
                return round($this->posts_count / 1000, 1).'K';
            }

            return (string) $this->posts_count;
        }

        return '1K';
    }
}
