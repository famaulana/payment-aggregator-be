<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\Shared\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $methods = PaymentMethod::with(['paymentGatewayMappings' => function ($q) {
                $q->where('status', 'active')
                  ->whereHas('paymentGateway', fn($gq) => $gq->where('status', 'active'));
            }])
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('method_name')
            ->get();

        // Group by method_type
        $grouped = $methods->groupBy(fn($m) => $m->method_type->value);

        $result = $grouped->map(function ($methodsInGroup, $methodType) {
            $first = $methodsInGroup->first();
            return [
                'method_code' => $methodType,
                'method_name' => $first->method_type->label(),
                'channels'    => $methodsInGroup->map(fn($m) => $this->buildChannel($m))->values(),
            ];
        })->values();

        return ResponseService::success($result);
    }

    private function buildChannel(PaymentMethod $method): array
    {
        $mapping = $method->paymentGatewayMappings->first();

        $fee = null;
        if ($mapping) {
            $fee = $mapping->fee_type === 'fixed'
                ? ['type' => 'fixed', 'amount' => (float) $mapping->fee_fixed]
                : ['type' => 'percentage', 'percentage' => (float) $mapping->fee_percentage];
        }

        return array_filter([
            'channel_code' => $method->method_code,
            'channel_name' => $method->method_name,
            'fee'          => $fee,
            'min_amount'   => $mapping?->min_amount ?? $method->min_amount,
            'max_amount'   => $mapping?->max_amount ?? $method->max_amount,
            'icon_url'     => $method->icon_url,
        ], fn($v) => $v !== null);
    }
}
