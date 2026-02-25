<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClientBalance;
use App\Services\Shared\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $apiKey = $request->get('api_key_record');
        $client = $apiKey->client;

        return ResponseService::success([
            'client_id'         => $client->id,
            'client_name'       => $client->client_name,
            'available_balance' => (float) $client->available_balance,
            'pending_balance'   => (float) $client->pending_balance,
            'minus_balance'     => (float) $client->minus_balance,
            'currency'          => 'IDR',
            'updated_at'        => $client->updated_at->toIso8601String(),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $apiKey = $request->get('api_key_record');
        $client = $apiKey->client;

        $query = ClientBalance::where('client_id', $client->id)
            ->orderByDesc('created_at');

        if ($request->filled('type')) {
            $query->where('transaction_type', $request->type);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        return ResponseService::pagination($paginator);
    }
}
