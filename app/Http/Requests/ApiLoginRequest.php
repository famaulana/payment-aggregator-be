<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ApiLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'api_key' => ['required', 'string', 'exists:api_keys,api_key'],
            'api_secret' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'api_key.required' => __('validation.api_key_required'),
            'api_key.exists' => __('auth.api_key_not_found'),
            'api_secret.required' => __('validation.api_secret_required'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'response_code' => '1003',
                'response_message' => __('auth.api_login_failed'),
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
