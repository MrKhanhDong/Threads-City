<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['post_id', 'view_date', 'views'])]
class PostDailyView extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['view_date' => 'date', 'views' => 'integer'];
    }

    protected function viewDate(): Attribute
    {
        return Attribute::make(set: fn ($value) => Carbon::parse($value)->toDateString());
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
