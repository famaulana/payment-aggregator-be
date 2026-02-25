<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SystemOwnerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'code'          => $this->code,
            'name'          => $this->name,
            'business_type' => $this->business_type,
            'pic_name'      => $this->pic_name,
            'pic_position'  => $this->pic_position,
            'pic_phone'     => $this->pic_phone,
            'pic_email'     => $this->pic_email,
            'company_phone' => $this->company_phone,
            'company_email' => $this->company_email,
            'province'      => $this->whenLoaded('province', fn() => [
                'id'   => $this->province->id,
                'name' => $this->province->name,
            ]),
            'city'          => $this->whenLoaded('city', fn() => [
                'id'   => $this->city->id,
                'name' => $this->city->name,
            ]),
            'address'       => $this->address,
            'postal_code'   => $this->postal_code,
            'status'        => $this->status,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
