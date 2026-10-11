<?php

namespace App\Services;

use App\Http\Requests\PostRequest;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostWriter
{
    public function save(PostRequest $request, ?Post $post = null): Post
    {
        $post ??= new Post;
        $data = $request->validated();
        $oldCover = $post->cover_path;
        $newCover = $request->hasFile('cover') ? $request->file('cover')->store('covers', 'public') : null;
        if ($request->hasFile('cover') && !$newCover) throw new \RuntimeException('Không lưu được ảnh bìa.');
        try {
            DB::transaction(function () use ($request, $post, $data, $oldCover, $newCover) {
                $slug = $data['slug'] ?? '';
                if ($slug === '') {
                    $base = rtrim(Str::substr(Str::slug($data['title']) ?: 'bai-viet', 0, 210), '-');
                    $slug = $base;
                    $suffix = 2;
                    while (Post::withTrashed()->where('slug', $slug)->when($post->exists, fn ($q) => $q->whereKeyNot($post->id))->exists()) $slug = $base.'-'.$suffix++;
                }
                $post->fill(collect($data)->only(['title', 'excerpt', 'content', 'category_id', 'status', 'cover_alt'])->all());
                $post->slug = $slug;
                $post->user_id = $post->exists ? $post->user_id : $request->user()->id;
                $post->is_featured = $request->boolean('is_featured');
                $post->published_at = $data['status'] === 'published' ? ($post->published_at ?? now()) : null;
                $post->cover_path = $newCover ?: ($request->boolean('remove_cover') ? null : $oldCover);
                $post->save();
                $post->tags()->sync($data['tags'] ?? []);
            });
        } catch (\Throwable $error) {
            if ($newCover) Storage::disk('public')->delete($newCover);
            if ($error instanceof \Illuminate\Database\UniqueConstraintViolationException) {
                throw \Illuminate\Validation\ValidationException::withMessages(['slug' => 'Đường dẫn vừa được sử dụng. Hãy chọn đường dẫn khác hoặc thử lưu lại.']);
            }
            throw $error;
        }
        if ($oldCover && $oldCover !== $post->cover_path && str_starts_with($oldCover, 'covers/')) Storage::disk('public')->delete($oldCover);
        return $post;
    }
}
