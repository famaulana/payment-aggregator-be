<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Transaction $this */
        $base = [
            'transaction_id'  => $this->transaction_id,
            'merchant_ref'    => $this->merchant_ref,
            // outlet = Merchant model (outlet/agen/cabang of the Client)
            'outlet' => $this->whenLoaded('merchant', fn() => [
                'code' => $this->merchant?->merchant_code,
                'name' => $this->merchant?->merchant_name,
            ]),
            'payment_method'  => $this->paymentMethod?->method_type->value,
            'payment_channel' => $this->getChannelName(),
            'status'          => $this->status->value,
            'amount'          => (float) $this->gross_amount,
            'currency'        => $this->currency ?? 'IDR',
            'fee'             => [
                'mdr_amount' => (float) $this->mdr_amount,
                'net_amount' => (float) $this->net_amount,
            ],
            'customer' => [
                'name'  => $this->customer_name,
                'email' => $this->customer_email,
                'phone' => $this->customer_phone,
            ],
            'expired_at'      => $this->expired_at?->toIso8601String(),
            'created_at'      => $this->created_at->toIso8601String(),
        ];

        // Add payment instructions
        $base['payment_instruction'] = $this->buildPaymentInstruction();

        // Include paid_at, settlement info when applicable
        if ($this->status === TransactionStatus::PAID || $this->paid_at) {
            $base['paid_at']           = $this->paid_at?->toIso8601String();
            $base['settlement_status'] = $this->settlement_status->value ?? null;
            $base['settlement_date']   = $this->settlement_date?->toDateString();
        }

        if ($this->metadata) {
            $base['metadata'] = $this->metadata;
        }

        return $base;
    }

    private function buildPaymentInstruction(): array
    {
        $methodType = $this->paymentMethod?->method_type->value;

        return match ($methodType) {
            'virtual_account' => array_filter([
                'va_number'    => $this->pg_va_number ?? $this->account_number,
                'bank'         => $this->getChannelName(),
                'account_name' => $this->customer_name,
            ]),
            'qris' => array_filter([
                'qr_string'    => $this->pg_qr_string,
                'qr_url'       => null,
                'qr_image_url' => null,
            ]),
            'e_wallet' => array_filter([
                'checkout_url' => $this->pg_checkout_url,
                'deeplink_url' => $this->pg_deeplink_url,
                'wallet'       => $this->getChannelName(),
            ]),
            'paylater' => array_filter([
                'checkout_url' => $this->pg_checkout_url,
                'provider'     => $this->getChannelName(),
            ]),
            default => [],
        };
    }

    private function getChannelName(): ?string
    {
        return $this->paymentMethod?->method_name;
    }
}
