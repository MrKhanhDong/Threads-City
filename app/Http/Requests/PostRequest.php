<?php

namespace App\Http\Requests;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $post = $this->route('post');
        $id = $post instanceof Post ? $post->id : null;

        return [
            'title' => 'required|string|max:200', 'slug' => ['nullable', 'string', 'max:220', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts')->ignore($id), Rule::unique('post_redirects', 'slug')->ignore($id, 'post_id')],
            'excerpt' => 'nullable|string|max:2000', 'content' => 'required|string|max:1000000',
            'status' => 'required|in:draft,published,scheduled', 'published_at' => ['nullable', 'date', Rule::requiredIf($this->input('status') === 'scheduled'), $this->input('status') === 'scheduled' ? 'after:now' : 'nullable'],
            'is_featured' => 'sometimes|boolean', 'categories' => 'required|array|min:1|max:20', 'categories.*' => 'required|integer|distinct|exists:categories,id',
            'tags' => 'nullable|array|max:30', 'tags.*' => 'integer|distinct|exists:tags,id',
            'cover_media_id' => 'nullable|integer|exists:media,id', 'og_media_id' => 'nullable|integer|exists:media,id',
            'seo_title' => 'nullable|string|max:200', 'meta_description' => 'nullable|string|max:320', 'focus_keyword' => 'nullable|string|max:150',
            'canonical_url' => 'nullable|url:http,https|max:500', 'og_title' => 'nullable|string|max:200', 'og_description' => 'nullable|string|max:320',
            'lock_version' => $id ? 'required|integer|min:1' : 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return ['categories.required' => 'Chọn ít nhất một danh mục.', 'content.required' => 'Nhập nội dung bài viết.', 'published_at.after' => 'Thời điểm lên lịch phải ở tương lai.'];
    }
}
