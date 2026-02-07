<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_id' => ['required', 'exists:questions,id'],
            'option_id' => ['required', 'exists:options,id'],
            'answered_at' => ['required', 'date'],
            'elapsed_seconds' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'question_id.required' => 'Question ID is required.',
            'question_id.exists' => 'Invalid question.',
            'option_id.required' => 'Option ID is required.',
            'option_id.exists' => 'Invalid option.',
            'answered_at.required' => 'Answer timestamp is required.',
            'answered_at.date' => 'Invalid timestamp format.',
        ];
    }
}
