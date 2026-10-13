<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return ['file' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=12000,max_height=12000', 'alt' => 'nullable|string|max:255'];
    }
}
