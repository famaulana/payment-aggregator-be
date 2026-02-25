<?php

namespace App\Services\Payment;

use App\Models\PaymentMethod;
use App\Models\PgPaymentMethodMapping;
use Illuminate\Support\Facades\Cache;

class PaymentRouterService
{
    /**
     * Find the best gateway+mapping for a given payment method + channel.
     *
     * Resolves the internal method_code from the API-level payment_method + channel,
     * then returns [PaymentMethod, PgPaymentMethodMapping(with paymentGateway)].
     *
     * Format yang diharapkan:
     * - payment_method: 'virtual_account', 'qris', 'e_wallet', 'paylater'
     * - payment_channel: 'va_bca', 'dana', 'ovo', dll (sudah dalam format method_code)
     *
     * Examples:
     *   virtual_account + va_bca  → va_bca
     *   qris                       → qris
     *   e_wallet + dana           → dana
     *   paylater + akulaku        → akulaku
     */
    public function route(string $paymentMethod, ?string $paymentChannel = null): array
    {
        $methodCode = $this->resolveMethodCode($paymentMethod, $paymentChannel);

        // Cache only the IDs to avoid serialization issues with Eloquent models
        $cacheKey = "payment_route_ids_{$methodCode}";

        [$pmId, $mappingId] = Cache::remember($cacheKey, 300, function () use ($methodCode) {
            $paymentMethod = PaymentMethod::where('method_code', $methodCode)
                ->where('status', 'active')
                ->firstOrFail();

            $mapping = PgPaymentMethodMapping::where('payment_method_id', $paymentMethod->id)
                ->where('status', 'active')
                ->whereHas('paymentGateway', fn($q) => $q->where('status', 'active'))
                ->orderByDesc('is_primary')
                ->orderBy('payment_gateway_id')
                ->firstOrFail();

            return [$paymentMethod->id, $mapping->id];
        });

        // Load fresh with relationships
        $paymentMethodModel = PaymentMethod::findOrFail($pmId);
        $mapping = PgPaymentMethodMapping::with('paymentGateway')->findOrFail($mappingId);

        return [$paymentMethodModel, $mapping];
    }

    /**
     * Resolve method_code dari payment_method dan payment_channel
     *
     * Jika payment_channel sudah dalam format method_code (va_bca, dana, dll),
     * langsung digunakan. Jika tidak, dibuat dari payment_method + channel.
     */
    private function resolveMethodCode(string $paymentMethod, ?string $channel): string
    {
        // Jika tidak ada channel, gunakan payment_method langsung
        if (!$channel) {
            return strtolower($paymentMethod);
        }

        $channel = strtolower($channel);

        // Untuk qris, langsung return karena tidak perlu channel
        if ($paymentMethod === 'qris') {
            return 'qris';
        }

        // Jika channel sudah dalam format yang benar (va_bca, dana, ovo, dll)
        // langsung gunakan sebagai method_code
        if (in_array($channel, ['va_bca', 'va_mandiri', 'va_bri', 'va_bni', 'dana', 'ovo', 'shopeepay', 'akulaku', 'kredivo'])) {
            return $channel;
        }

        // Fallback: construct dari payment_method + channel
        return match ($paymentMethod) {
            'virtual_account'  => 'va_' . $channel,
            'e_wallet'         => $channel,
            'paylater'         => $channel,
            default            => strtolower($paymentMethod),
        };
    }
}
