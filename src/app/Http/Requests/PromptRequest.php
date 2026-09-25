<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromptRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:4000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'prompt.required' => 'Please enter a prompt.',
            'prompt.max' => 'Your prompt may not be longer than 4000 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'prompt' => is_string($this->input('prompt')) ? trim($this->input('prompt')) : $this->input('prompt'),
        ]);
    }
}
