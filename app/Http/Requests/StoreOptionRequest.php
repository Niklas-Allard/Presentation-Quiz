<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question_id' => ['required', 'exists:questions,id'],
            'text' => ['required', 'string', 'max:200'],
            'is_correct' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'Option text is required.',
            'text.max' => 'Option text cannot exceed 200 characters.',
        ];
    }
}
