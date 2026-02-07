<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'head_office_id' => $this->head_office_id,
            'merchant_code' => $this->merchant_code,
            'merchant_name' => $this->merchant_name,
            'province_id' => $this->province_id,
            'city_id' => $this->city_id,
            'district_id' => $this->district_id,
            'sub_district_id' => $this->sub_district_id,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'phone' => $this->phone,
            'email' => $this->email,
            'pos_merchant_id' => $this->pos_merchant_id,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'client_name' => $this->client->client_name,
                    'client_code' => $this->client->client_code,
                ];
            }),

            'head_office' => $this->whenLoaded('headOffice', function () {
                return $this->headOffice ? [
                    'id' => $this->headOffice->id,
                    'name' => $this->headOffice->name,
                    'code' => $this->headOffice->code,
                ] : null;
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

            'district' => $this->whenLoaded('district', function () {
                return $this->district ? [
                    'id' => $this->district->id,
                    'name' => $this->district->name,
                ] : null;
            }),

            'sub_district' => $this->whenLoaded('subDistrict', function () {
                return $this->subDistrict ? [
                    'id' => $this->subDistrict->id,
                    'name' => $this->subDistrict->name,
                ] : null;
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
