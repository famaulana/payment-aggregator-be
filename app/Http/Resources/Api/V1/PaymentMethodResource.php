<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var PaymentMethod $this */
        return [
            'method_code'       => $this->method_type->value,
            'method_name'       => $this->method_type->label(),
            'icon_url'          => $this->icon_url,
            'min_amount'        => $this->min_amount,
            'max_amount'        => $this->max_amount,
            'is_redirect_based' => (bool) $this->is_redirect_based,
            'sort_order'        => $this->sort_order,
            'channels'          => $this->buildChannels(),
        ];
    }

    private function buildChannels(): array
    {
        return $this->paymentGatewayMappings
            ->where('status', 'active')
            ->map(function ($mapping) {
                $fee = $mapping->fee_type === 'fixed'
                    ? ['type' => 'fixed', 'amount' => (float) $mapping->fee_fixed]
                    : ['type' => 'percentage', 'percentage' => (float) $mapping->fee_percentage];

                return [
                    'channel_code' => strtoupper(str_replace(['va_', 'emoney_', 'flazz_', 'brizzi_', 'tapcash_'], '', $this->method_code)),
                    'channel_name' => $this->method_name,
                    'fee'          => $fee,
                    'min_amount'   => $mapping->min_amount ?? $this->min_amount,
                    'max_amount'   => $mapping->max_amount ?? $this->max_amount,
                ];
            })
            ->values()
            ->toArray();
    }
}
