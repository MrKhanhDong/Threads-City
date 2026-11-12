<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['body' => 'required|string|min:2|max:5000', 'parent_id' => 'nullable|integer|exists:comments,id', 'mention_ids' => 'nullable|array|max:6', 'mention_ids.*' => 'integer|distinct|exists:users,id'];
    }
}
