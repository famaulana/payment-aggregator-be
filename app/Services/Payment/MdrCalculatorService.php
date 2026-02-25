<?php

namespace App\Services\Payment;

use App\Models\PgPaymentMethodMapping;

class MdrCalculatorService
{
    /**
     * Calculate fee breakdown for a payment.
     *
     * @return array{vendor_margin: float, our_margin: float, mdr_amount: float, net_amount: float}
     */
    public function calculate(PgPaymentMethodMapping $mapping, float $amount): array
    {
        // MDR charged to client (harga jual)
        $mdrAmount = $mapping->calculateClientFee($amount);

        // Vendor cost (modal) — what we pay to PG
        $vendorMargin = match ($mapping->vendor_margin_type->value) {
            'fixed'      => (float) $mapping->vendor_margin_fixed,
            'percentage' => round($amount * ((float) $mapping->vendor_margin_percentage / 100), 2),
            'mixed'      => (float) $mapping->vendor_margin_fixed
                          + round($amount * ((float) $mapping->vendor_margin_percentage / 100), 2),
            default      => 0,
        };

        // Our profit (keuntungan) = harga jual - modal
        $ourMargin = $mdrAmount - $vendorMargin;

        // Net amount received by merchant
        $netAmount = $amount - $mdrAmount;

        return [
            'vendor_margin' => round($vendorMargin, 2),
            'our_margin'    => round($ourMargin, 2),
            'mdr_amount'    => round($mdrAmount, 2),
            'net_amount'    => round($netAmount, 2),
        ];
    }
}
