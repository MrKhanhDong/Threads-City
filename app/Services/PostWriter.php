<?php

namespace App\Services;

use App\Http\Requests\PostRequest;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostRedirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PostWriter
{
    public function __construct(private HtmlSanitizer $sanitizer) {}

    public function save(PostRequest $request, ?Post $article = null): Post
    {
        $data = $request->validated();
        try {
            return DB::transaction(function () use ($request, $article, $data): Post {
                $post = $article ? Post::lockForUpdate()->findOrFail($article->id) : new Post;
                if ($post->exists && (int) $data['lock_version'] !== $post->lock_version) {
                    throw ValidationException::withMessages(['lock_version' => 'Bài đã thay đổi ở cửa sổ khác. Sao chép nội dung đang viết, tải lại rồi lưu để tránh ghi đè.']);
                }
                $slug = $data['slug'] ?? '';
                if (! $slug) {
                    $base = Str::limit(Str::slug($data['title']) ?: 'bai-viet', 200, '');
                    $slug = $base;
                    $suffix = 2;
                    while (Post::withTrashed()->where('slug', $slug)->when($post->exists, fn (Builder $query): Builder => $query->whereKeyNot($post->id))->exists() || PostRedirect::where('slug', $slug)->when($post->exists, fn (Builder $query): Builder => $query->where('post_id', '!=', $post->id))->exists()) {
                        $slug = $base.'-'.$suffix++;
                    }
                }
                $previousSlug = $post->slug;
                $wasPublic = $post->published_at && $post->published_at->lte(now());
                $post->fill(collect($data)->except(['categories', 'tags', 'lock_version'])->all());
                $post->slug = $slug;
                $post->content = $this->sanitizer->clean($data['content']);
                if (trim(strip_tags($post->content)) === '' && ! str_contains($post->content, '<img')) {
                    throw ValidationException::withMessages(['content' => 'Nội dung bài cần có chữ hoặc ảnh hợp lệ.']);
                }
                $post->user_id = $post->exists ? $post->user_id : $request->user()->id;
                $post->is_featured = $request->boolean('is_featured');
                $post->published_at = match ($data['status']) {
                    'draft' => null,
                    'scheduled' => $data['published_at'],
                    default => $post->published_at?->lte(now()) ? $post->published_at : now(),
                };
                $post->lock_version = $post->exists ? $post->lock_version + 1 : 1;
                $post->save();
                $post->categories()->sync($data['categories']);
                $post->tags()->sync($data['tags'] ?? []);
                $mediaIds = $this->sanitizer->mediaIds($post->content);
                if (Media::whereKey($mediaIds)->count() !== count($mediaIds)) {
                    throw ValidationException::withMessages(['content' => 'Có ảnh đã bị xóa khỏi thư viện. Hãy tải lại hoặc thay ảnh trước khi lưu.']);
                }
                $post->media()->sync($mediaIds);
                PostRedirect::where('slug', $slug)->where('post_id', $post->id)->delete();
                if ($previousSlug && $previousSlug !== $slug && $wasPublic) {
                    PostRedirect::firstOrCreate(['slug' => $previousSlug], ['post_id' => $post->id]);
                }

                return $post;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['slug' => 'Đường dẫn vừa được sử dụng. Chọn đường dẫn khác và lưu lại.']);
        }
    }
}
