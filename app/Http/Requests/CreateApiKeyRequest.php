<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isSystemOwner() || auth()->user()->isClientUser());
    }

    public function rules(): array
    {
        $user = auth()->user();

        $rules = [
            'key_name' => ['required', 'string', 'max:255'],
            'environment' => ['required', 'in:dev,staging,production'],
            'ip_whitelist' => ['nullable', 'array'],
            'ip_whitelist.*' => ['ip'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'rate_limit_per_hour' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($user->isSystemOwner()) {
            $rules['client_id'] = ['required', 'integer', 'exists:clients,id'];
        } elseif ($user->isClientUser()) {
            $rules['client_id'] = ['nullable', 'integer', 'exists:clients,id'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'client_id.required' => __('validation.client_id_required'),
            'client_id.exists' => __('validation.client_not_found'),
            'key_name.required' => __('validation.key_name_required'),
            'environment.required' => __('validation.environment_required'),
            'environment.in' => __('validation.environment_invalid'),
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
