<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();

        return auth()->check() && (
            $user->isSystemOwner() ||
            $user->isClientUser() ||
            $user->isHeadOfficeUser()
        );
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'username' => ['sometimes', 'string', 'max:255', 'unique:users,username,' . $userId],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $userId],
            'full_name' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', 'string', 'exists:roles,name'],
            'status' => ['sometimes', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => __('validation.username_exists'),
            'email.unique' => __('validation.email_exists'),
            'role.exists' => __('validation.role_not_found'),
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
