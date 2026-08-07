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
            'sim_status' => ['nullable', 'string', 'max:64'],
            'reg_status' => ['nullable', 'string', 'max:64'],
        ];
    }
}
