<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;

class BlogRepository
{
    public function savedIds(): array
    {
        return auth()->check() ? auth()->user()->bookmarks()->pluck('posts.id')->map(fn ($id) => (int) $id)->all() : [];
    }
    public function shared(): array
    {
        $owner = User::where('role', 'admin')->orderBy('id')->first();
        $name = $owner?->name ?? 'Chủ blog';
        $words = preg_split('/\s+/u', trim($name)) ?: [];
        $initials = collect(array_slice($words, -2))->map(fn ($word) => mb_substr($word, 0, 1))->implode('');
        $categories = Category::where('is_active', true)->withCount(['posts' => fn ($q) => $q->published()])->orderBy('name')->get()
            ->map(fn ($category) => ['id' => $category->id, 'slug' => $category->slug, 'name' => $category->name, 'description' => $category->description, 'icon' => $category->icon, 'count' => $category->posts_count]);
        $savedIds = $this->savedIds();
        return [
            'author' => ['name' => $name, 'initials' => mb_strtoupper($initials), 'role' => $owner?->headline ?? 'Người viết blog', 'bio' => $owner?->bio ?? 'Một nơi để ghi chép và chia sẻ.', 'about' => $owner?->about, 'email' => $owner?->email, 'website' => $owner?->website,
                'avatar' => $owner?->avatar_path ? route('media', ['path' => $owner->avatar_path]) : null],
            'blogTitle' => $owner?->blog_title ?? "Khang's Journal", 'categories' => $categories,
            'allPosts' => Post::published()->with(['category', 'tags', 'user'])->latest('published_at')->limit(2)->get()->map(fn (Post $post) => $post->toCard($savedIds)),
            'postCount' => Post::published()->count(),
        ];
    }
}
