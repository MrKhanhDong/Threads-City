<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'path', 'original_name', 'mime_type', 'size', 'width', 'height', 'alt', 'duration_ms', 'waveform'])]
class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected function casts(): array
    {
        return ['waveform' => 'array', 'duration_ms' => 'integer'];
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_media');
    }

    public function url(): string
    {
        return route('media.show', $this->id);
    }
}
