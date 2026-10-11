<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class PostDailyView extends Model
{
    protected $fillable = ['post_id', 'view_date', 'views'];

    protected function viewDate(): Attribute
    {
        // Keep DATE values canonical on MySQL and SQLite, without a time suffix.
        return Attribute::make(set: fn ($value) => Carbon::parse($value)->toDateString());
    }

    protected function casts(): array
    {
        return ['view_date' => 'date', 'views' => 'integer'];
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
