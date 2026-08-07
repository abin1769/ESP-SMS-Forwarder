<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSmsRequest extends FormRequest
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
            'token' => ['nullable', 'string', 'max:64'],
            'phone' => ['required', 'string', 'max:32'],
            'message' => ['required', 'string'],
            'received_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'processed' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'Phone number is required.',
            'message.required' => 'SMS message content is required.',
            'received_at.date_format' => 'Received at timestamp must follow Y-m-d H:i:s format.',
        ];
    }
}
