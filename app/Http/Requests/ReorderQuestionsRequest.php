<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'questions' => ['required', 'array'],
            'questions.*.id' => ['required', 'exists:questions,id'],
            'questions.*.order' => ['required', 'integer', 'min:0'],
            'questions.*.group_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
