<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateUserRequest extends FormRequest
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
        $user = auth()->user();

        $rules = [
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'full_name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'status' => ['nullable', 'in:active,inactive'],
        ];

        if ($user->isSystemOwner()) {
            $rules['entity_type'] = ['required', 'in:client'];
            $rules['entity_id'] = ['required', 'integer'];
        } elseif ($user->isClientUser()) {
            $rules['entity_type'] = ['required', 'in:head_office,merchant'];
            $rules['entity_id'] = ['required', 'integer'];
        } elseif ($user->isHeadOfficeUser()) {
            $rules['entity_type'] = ['required', 'in:merchant'];
            $rules['entity_id'] = ['required', 'integer'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'username.required' => __('validation.username_required'),
            'username.unique' => __('validation.username_exists'),
            'email.required' => __('validation.email_required'),
            'email.unique' => __('validation.email_exists'),
            'password.required' => __('validation.password_required'),
            'password.confirmed' => __('validation.password_confirmation_mismatch'),
            'full_name.required' => __('validation.full_name_required'),
            'role.required' => __('validation.role_required'),
            'role.exists' => __('validation.role_not_found'),
            'entity_type.required' => __('validation.entity_type_required'),
            'entity_id.required' => __('validation.entity_id_required'),
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
