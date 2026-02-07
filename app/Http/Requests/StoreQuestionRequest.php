<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'presentation_id' => ['required', 'exists:presentations,id'],
            'content' => ['required', 'array'],
            'content.text' => ['required', 'string', 'max:500'],
            'content.image_url' => ['nullable', 'url', 'max:2048'],
            'time_limit_seconds' => ['required', 'integer', 'min:5', 'max:300'],
            'group_name' => ['nullable', 'string', 'max:100'],
            'order' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.text.required' => 'Question text is required.',
            'content.text.max' => 'Question text cannot exceed 500 characters.',
            'time_limit_seconds.required' => 'Time limit is required.',
            'time_limit_seconds.min' => 'Time limit must be at least 5 seconds.',
            'time_limit_seconds.max' => 'Time limit cannot exceed 300 seconds (5 minutes).',
        ];
    }
}
