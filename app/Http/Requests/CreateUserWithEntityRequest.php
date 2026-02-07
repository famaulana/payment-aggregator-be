<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateUserWithEntityRequest extends FormRequest
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
        $entityType = $this->input('entity_type');

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
            $rules = array_merge($rules, $this->getClientRules());
        } elseif ($user->isClientUser()) {
            $rules['entity_type'] = ['required', 'in:head_office,merchant'];

            if ($entityType === 'head_office') {
                $rules = array_merge($rules, $this->getHeadOfficeRules());
            } elseif ($entityType === 'merchant') {
                $rules = array_merge($rules, $this->getMerchantRules());
            }
        } elseif ($user->isHeadOfficeUser()) {
            $rules['entity_type'] = ['required', 'in:merchant'];
            $rules = array_merge($rules, $this->getMerchantRules());
        }

        return $rules;
    }

    private function getClientRules(): array
    {
        return [
            'client_code' => ['required', 'string', 'max:50', 'unique:clients,client_code'],
            'client_name' => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_holder_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'pic_position' => ['nullable', 'string', 'max:100'],
            'pic_phone' => ['nullable', 'string', 'max:20'],
            'pic_email' => ['nullable', 'email', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:20'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],
        ];
    }

    private function getHeadOfficeRules(): array
    {
        return [
            'head_office_code' => ['required', 'string', 'max:50'],
            'head_office_name' => ['required', 'string', 'max:255'],
            'province_id' => ['required', 'exists:provinces,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'sub_district_id' => ['nullable', 'exists:sub_districts,id'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'ho_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    private function getMerchantRules(): array
    {
        $user = auth()->user();

        $rules = [
            'merchant_code' => ['required', 'string', 'max:50', 'unique:merchants,merchant_code'],
            'merchant_name' => ['required', 'string', 'max:255'],
            'province_id' => ['required', 'exists:provinces,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'sub_district_id' => ['nullable', 'exists:sub_districts,id'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'merchant_email' => ['nullable', 'email', 'max:255'],
            'pos_merchant_id' => ['nullable', 'string', 'max:100'],
        ];

        if ($user->isClientUser()) {
            $rules['head_office_id'] = ['nullable', 'exists:head_offices,id'];
        }

        return $rules;
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
