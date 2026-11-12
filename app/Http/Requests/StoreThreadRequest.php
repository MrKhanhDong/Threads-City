<?php

namespace App\Http\Requests;

use App\Services\ThreadText;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreThreadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $published = fn (Builder $query): Builder => $query->whereNull('deleted_at')->whereNull('archived_at')->whereIn('status', ['published', 'scheduled'])->where('published_at', '<=', now());

        return [
            'content' => ['required_without:media_ids', 'nullable', 'string', $this->input('content_format') === 'html' ? 'max:20000' : 'max:500'],
            'content_format' => ['sometimes', 'in:plain,html'],
            'media_ids' => ['nullable', 'array', 'max:4'],
            'media_ids.*' => ['required', 'integer', 'distinct', Rule::exists('media', 'id')->where('user_id', $this->user()?->id)],
            'parent_id' => ['nullable', 'integer', Rule::prohibitedIf($this->filled('repost_of_id')), Rule::exists('posts', 'id')->where($published)],
            'repost_of_id' => ['nullable', 'integer', Rule::exists('posts', 'id')->where($published)],
            'continuations' => ['nullable', 'array', 'max:9'],
            'continuations.*' => ['required', 'string', $this->input('content_format') === 'html' ? 'max:20000' : 'max:500'],
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('content_format') !== 'html') {
                return;
            }
            $text = app(ThreadText::class);
            $fields = ['content' => $this->input('content', '')];
            if (is_array($this->input('continuations'))) {
                foreach ($this->input('continuations') as $index => $content) {
                    $fields['continuations.'.$index] = $content;
                }
            }
            foreach ($fields as $field => $content) {
                if (! is_string($content)) {
                    continue;
                }
                $plain = $text->plain($text->clean($content));
                if ($plain === '' && ($field !== 'content' || empty($this->input('media_ids')))) {
                    $validator->errors()->add($field, 'Nhập nội dung hoặc đính kèm ảnh cho bài viết.');
                }
                if (mb_strlen($plain, 'UTF-8') > 500) {
                    $validator->errors()->add($field, 'Mỗi bài viết tối đa 500 ký tự, không tính định dạng.');
                }
            }
        }];
    }
}
