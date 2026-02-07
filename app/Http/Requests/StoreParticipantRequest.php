<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParticipantRequest extends FormRequest
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
            'display_name' => ['required', 'string', 'max:50', 'min:2'],
            'presentation_id' => ['required', 'exists:presentations,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'display_name.required' => 'Please enter your display name.',
            'display_name.min' => 'Display name must be at least 2 characters.',
            'display_name.max' => 'Display name cannot exceed 50 characters.',
            'presentation_id.exists' => 'The specified presentation does not exist.',
        ];
    }
}
