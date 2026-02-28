<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();

        return auth()->check() && (
            $user->isSystemOwner() ||
            $user->isClientUser() ||
            $user->isHeadQuarterUser()
        );
    }

    public function rules(): array
    {
        $targetUser = $this->resolveTargetUser();
        $userId     = $targetUser?->id;
        $isSelf     = $this->route('id') === null;

        $rules = [
            'username'  => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'email'     => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'full_name' => ['sometimes', 'string', 'max:255'],
        ];

        // Role and status can only be changed when updating another user (not self)
        if (!$isSelf) {
            $rules['role']   = ['sometimes', 'string', 'exists:roles,name'];
            $rules['status'] = ['sometimes', 'in:active,inactive'];
        }

        // Merge entity-specific validation rules based on the target user's entity type
        if ($targetUser) {
            $rules = array_merge($rules, $this->entityRules($targetUser));
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'username.unique' => __('validation.username_exists'),
            'email.unique'    => __('validation.email_exists'),
            'role.exists'     => __('validation.role_not_found'),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveTargetUser(): ?User
    {
        $id = $this->route('id');

        if ($id) {
            return User::find($id);
        }

        // Try both guards to ensure we get the authenticated user
        return auth()->user() ?? auth('api')->user();
    }

    private function entityRules(User $user): array
    {
        if ($user->isSystemOwner()) {
            return [
                'name'           => ['sometimes', 'string', 'max:255'],
                'business_type'  => ['nullable', 'string', 'max:100'],
                'pic_name'       => ['nullable', 'string', 'max:255'],
                'pic_position'   => ['nullable', 'string', 'max:100'],
                'pic_phone'      => ['nullable', 'string', 'max:20'],
                'pic_email'      => ['nullable', 'email', 'max:255'],
                'company_phone'  => ['nullable', 'string', 'max:20'],
                'company_email'  => ['nullable', 'email', 'max:255'],
                'province_id'    => ['nullable', 'exists:provinces,id'],
                'city_id'        => ['nullable', 'exists:cities,id'],
                'address'        => ['nullable', 'string'],
                'postal_code'    => ['nullable', 'string', 'max:10'],
            ];
        }

        if ($user->isClientUser()) {
            return [
                'client_name'              => ['sometimes', 'string', 'max:255'],
                'business_type'            => ['nullable', 'string', 'max:100'],
                'bank_name'                => ['nullable', 'string', 'max:100'],
                'bank_account_number'      => ['nullable', 'string', 'max:50'],
                'bank_account_holder_name' => ['nullable', 'string', 'max:255'],
                'bank_branch'              => ['nullable', 'string', 'max:255'],
                'pic_name'                 => ['nullable', 'string', 'max:255'],
                'pic_position'             => ['nullable', 'string', 'max:100'],
                'pic_phone'                => ['nullable', 'string', 'max:20'],
                'pic_email'                => ['nullable', 'email', 'max:255'],
                'company_phone'            => ['nullable', 'string', 'max:20'],
                'company_email'            => ['nullable', 'email', 'max:255'],
                'province_id'              => ['nullable', 'exists:provinces,id'],
                'city_id'                  => ['nullable', 'exists:cities,id'],
                'address'                  => ['nullable', 'string'],
                'postal_code'              => ['nullable', 'string', 'max:10'],
            ];
        }

        if ($user->isHeadQuarterUser()) {
            return [
                'name'            => ['sometimes', 'string', 'max:255'],
                'province_id'     => ['nullable', 'exists:provinces,id'],
                'city_id'         => ['nullable', 'exists:cities,id'],
                'district_id'     => ['nullable', 'exists:districts,id'],
                'sub_district_id' => ['nullable', 'exists:sub_districts,id'],
                'address'         => ['nullable', 'string'],
                'postal_code'     => ['nullable', 'string', 'max:10'],
                'phone'           => ['nullable', 'string', 'max:20'],
                'ho_email'        => ['nullable', 'email', 'max:255'],
            ];
        }

        if ($user->isMerchantUser()) {
            return [
                'merchant_name'   => ['sometimes', 'string', 'max:255'],
                'province_id'     => ['nullable', 'exists:provinces,id'],
                'city_id'         => ['nullable', 'exists:cities,id'],
                'district_id'     => ['nullable', 'exists:districts,id'],
                'sub_district_id' => ['nullable', 'exists:sub_districts,id'],
                'address'         => ['nullable', 'string'],
                'postal_code'     => ['nullable', 'string', 'max:10'],
                'phone'           => ['nullable', 'string', 'max:20'],
                'merchant_email'  => ['nullable', 'email', 'max:255'],
                'pos_merchant_id' => ['nullable', 'string', 'max:100'],
            ];
        }

        return [];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'response_code'    => '1001',
                'response_message' => __('messages.validation_failed'),
                'errors'           => $validator->errors(),
            ], 422)
        );
    }

    /**
     * Extract only entity-related data from the validated request.
     * This filters out user fields (username, email, full_name, role, status)
     * and returns only the entity-specific fields.
     */
    public function getEntityData(): array
    {
        $targetUser = $this->resolveTargetUser();

        if (!$targetUser) {
            return [];
        }

        $validated = $this->validated();
        $entityFields = [];

        // Get entity field names based on target user's entity type
        if ($targetUser->isSystemOwner()) {
            $entityFields = [
                'name',
                'business_type',
                'pic_name',
                'pic_position',
                'pic_phone',
                'pic_email',
                'company_phone',
                'company_email',
                'province_id',
                'city_id',
                'address',
                'postal_code',
            ];
        } elseif ($targetUser->isClientUser()) {
            $entityFields = [
                'client_name',
                'business_type',
                'bank_name',
                'bank_account_number',
                'bank_account_holder_name',
                'bank_branch',
                'pic_name',
                'pic_position',
                'pic_phone',
                'pic_email',
                'company_phone',
                'company_email',
                'province_id',
                'city_id',
                'address',
                'postal_code',
            ];
        } elseif ($targetUser->isHeadQuarterUser()) {
            $entityFields = [
                'name',
                'province_id',
                'city_id',
                'district_id',
                'sub_district_id',
                'address',
                'postal_code',
                'phone',
                'ho_email',
            ];
        } elseif ($targetUser->isMerchantUser()) {
            $entityFields = [
                'merchant_name',
                'province_id',
                'city_id',
                'district_id',
                'sub_district_id',
                'address',
                'postal_code',
                'phone',
                'merchant_email',
                'pos_merchant_id',
            ];
        }

        // Filter validated data to only include entity fields
        return array_intersect_key($validated, array_flip($entityFields));
    }
}
