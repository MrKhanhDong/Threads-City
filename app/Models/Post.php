<?php

namespace App\Models;

use App\Support\ArticleText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'category_id', 'title', 'slug', 'excerpt', 'content', 'cover_path', 'cover_alt', 'status', 'is_featured', 'published_at'];
    protected function casts(): array { return ['is_featured' => 'boolean', 'published_at' => 'datetime']; }
    public function user() { return $this->belongsTo(User::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function tags() { return $this->belongsToMany(Tag::class); }
    public function comments() { return $this->hasMany(Comment::class); }
    public function dailyViews() { return $this->hasMany(PostDailyView::class); }
    public function bookmarkedBy() { return $this->belongsToMany(User::class, 'bookmarks')->withPivot('created_at'); }
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now())
            ->whereHas('category', fn (Builder $category) => $category->where('is_active', true));
    }
    public function toCard(array $savedIds = []): array
    {
        $date = $this->published_at ?? $this->updated_at;
        return [
            'id' => $this->id, 'slug' => $this->slug, 'title' => $this->title, 'excerpt' => $this->excerpt,
            'category' => $this->category->name, 'category_slug' => $this->category->slug,
            'date' => $date->format('Y-m-d'), 'date_label' => $date->format('d').' tháng '.$date->format('m, Y'),
            'read_time' => ArticleText::readingMinutes($this->content),
            'image' => $this->cover_path ? (str_starts_with($this->cover_path, 'images/') ? asset($this->cover_path) : route('media', ['path' => $this->cover_path])) : null,
            'image_alt' => $this->cover_alt ?? $this->title,
            'tags' => $this->tags->pluck('name')->all(), 'sections' => ArticleText::sections($this->content),
            'saved' => in_array($this->id, $savedIds, true), 'author_name' => $this->user->name,
        ];
    }
}
