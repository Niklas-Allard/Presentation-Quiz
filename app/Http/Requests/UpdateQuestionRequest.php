<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['sometimes', 'array'],
            'content.text' => ['sometimes', 'string', 'max:500'],
            'content.image_url' => ['nullable', 'url', 'max:2048'],
            'time_limit_seconds' => ['sometimes', 'integer', 'min:5', 'max:300'],
            'group_name' => ['nullable', 'string', 'max:100'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
