<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DeviceHeartbeatRequest extends FormRequest
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
            'signal' => ['nullable', 'integer', 'between:0,31'],
            'operator' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'signal.between' => 'Signal strength must be between 0 and 31.',
            'operator.max' => 'Operator name cannot exceed 64 characters.',
        ];
    }
}
