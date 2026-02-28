<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $userId = $this->route('id');
        $isSelf = $userId === null;

        $rules = [
            'old_password' => ['required', 'string', 'min:8'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        // If updating another user (not self), old_password is not required
        // This is for admin reset scenario
        if (!$isSelf) {
            unset($rules['old_password']);
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'old_password.required' => 'The old password field is required.',
            'old_password.min' => 'The old password must be at least 8 characters.',
            'old_password.current_password' => 'The old password is incorrect.',
            'password.required' => 'The new password field is required.',
            'password.min' => 'The new password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $oldPassword = $validator->safe()->only('old_password')['old_password'] ?? null;
            $newPassword = $validator->safe()->only('password')['password'] ?? null;
            $userId = $this->route('id');
            $isSelf = $userId === null;

            // For self-update, verify old password matches current password
            if ($isSelf && $oldPassword && $newPassword) {
                $currentUser = auth()->user();

                if (!\Hash::check($oldPassword, $currentUser->password)) {
                    $validator->errors()->add('old_password', 'The old password is incorrect.');
                }
            }
        });
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
