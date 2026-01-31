<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isSystemOwner() || auth()->user()->isClientUser());
    }

    public function rules(): array
    {
        return [
            'key_name' => ['nullable', 'string', 'max:255'],
            'ip_whitelist' => ['nullable', 'array'],
            'ip_whitelist.*' => ['ip'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'rate_limit_per_hour' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'key_name.string' => __('validation.key_name_string'),
            'ip_whitelist.*.ip' => __('validation.ip_whitelist_invalid'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'response_code' => '1001',
                'response_message' => __('messages.validation_failed'),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
