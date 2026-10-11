<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
{
    public function authorize(): bool { return (bool) $this->user()?->isAdmin(); }
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'slug' => ['nullable', 'string', 'max:240', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('posts')->ignore($this->route('post')?->id)],
            'excerpt' => 'nullable|string|max:500', 'content' => 'required|string|min:10|max:100000',
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'tags' => 'nullable|array|max:20', 'tags.*' => 'integer|distinct|exists:tags,id',
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'is_featured' => 'sometimes|boolean',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'cover_alt' => 'nullable|string|max:200', 'remove_cover' => 'sometimes|boolean',
        ];
    }
    public function attributes(): array
    {
        return ['title' => 'tiêu đề', 'slug' => 'đường dẫn', 'content' => 'nội dung', 'category_id' => 'chuyên mục', 'cover' => 'ảnh bìa'];
    }
}
