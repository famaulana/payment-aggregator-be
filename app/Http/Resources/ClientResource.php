<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'system_owner_id' => $this->system_owner_id,
            'client_code' => $this->client_code,
            'client_name' => $this->client_name,
            'business_type' => $this->business_type,
            'kyb_status' => $this->kyb_status,
            'kyb_submitted_at' => $this->kyb_submitted_at,
            'kyb_approved_at' => $this->kyb_approved_at,
            'kyb_rejected_at' => $this->kyb_rejected_at,
            'kyb_rejection_reason' => $this->kyb_rejection_reason,
            'settlement_time' => $this->settlement_time,
            'settlement_config' => $this->settlement_config,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_holder_name' => $this->bank_account_holder_name,
            'bank_branch' => $this->bank_branch,
            'pic_name' => $this->pic_name,
            'pic_position' => $this->pic_position,
            'pic_phone' => $this->pic_phone,
            'pic_email' => $this->pic_email,
            'company_phone' => $this->company_phone,
            'company_email' => $this->company_email,
            'available_balance' => $this->available_balance,
            'pending_balance' => $this->pending_balance,
            'minus_balance' => $this->minus_balance,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'system_owner' => $this->whenLoaded('systemOwner', function () {
                return [
                    'id' => $this->systemOwner->id,
                    'name' => $this->systemOwner->name,
                    'code' => $this->systemOwner->code,
                ];
            }),

            'province' => $this->whenLoaded('province', function () {
                return [
                    'id' => $this->province->id,
                    'name' => $this->province->name,
                ];
            }),

            'city' => $this->whenLoaded('city', function () {
                return [
                    'id' => $this->city->id,
                    'name' => $this->city->name,
                ];
            }),

            'creator' => $this->whenLoaded('creator', function () {
                return $this->creator ? [
                    'id' => $this->creator->id,
                    'username' => $this->creator->username,
                    'full_name' => $this->creator->full_name,
                ] : null;
            }),
        ];
    }
}
