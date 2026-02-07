<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'entity_type_label' => $this->getEntityTypeLabel(),
            'entity_name' => $this->getEntityName(),
            'status' => $this->status,
            'role' => $this->role_name,
            'permissions' => $this->permissions_list,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'entity' => $this->whenLoaded('entity', function () {
                if (!$this->entity) {
                    return null;
                }

                return match($this->entity_type) {
                    'App\Models\SystemOwner' => [
                        'id' => $this->entity->id,
                        'name' => $this->entity->name,
                        'code' => $this->entity->code,
                    ],
                    'App\Models\Client' => [
                        'id' => $this->entity->id,
                        'client_name' => $this->entity->client_name,
                        'client_code' => $this->entity->client_code,
                    ],
                    'App\Models\HeadOffice' => [
                        'id' => $this->entity->id,
                        'name' => $this->entity->name,
                        'code' => $this->entity->code,
                        'client_id' => $this->entity->client_id,
                    ],
                    'App\Models\Merchant' => [
                        'id' => $this->entity->id,
                        'merchant_name' => $this->entity->merchant_name,
                        'merchant_code' => $this->entity->merchant_code,
                        'client_id' => $this->entity->client_id,
                        'head_office_id' => $this->entity->head_office_id,
                    ],
                    default => null,
                };
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
