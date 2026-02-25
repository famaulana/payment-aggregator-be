<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\CreatePaymentDTO;
use App\Enums\ResponseCode;
use App\Enums\TransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreatePaymentRequest;
use App\Http\Requests\Api\V1\RefundPaymentRequest;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Client;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Services\Payment\PaymentService;
use App\Services\Shared\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {}

    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/payments — Create Payment
    // ─────────────────────────────────────────────────────────────────────────

    public function create(CreatePaymentRequest $request): JsonResponse
    {
        try {
            // Resolve client from API Key
            // (API key belongs to Client — Client = mitra/partner of JDP)
            $apiKey = $request->get('api_key_record');
            $client = $apiKey->client;

            if (!$client) {
                return ResponseService::error(ResponseCode::CLIENT_NOT_FOUND);
            }

            // Resolve merchant (outlet/agen/cabang of the client)
            // merchant_code is optional — defaults to first active outlet
            $merchant = $this->resolveMerchant($client, $request->input('merchant_code'));

            if (!$merchant) {
                return ResponseService::error(
                    ResponseCode::MERCHANT_NOT_FOUND,
                    'No active outlet/location found for this client. Please set up a merchant outlet first.'
                );
            }

            // Idempotency: if an active (non-terminal) transaction already exists
            // for this client + merchant_ref, return it instead of creating a duplicate.
            $existing = Transaction::with(['paymentMethod', 'paymentGateway'])
                ->where('client_id', $client->id)
                ->where('merchant_ref', $request->merchant_ref)
                ->whereNotIn('status', [
                    TransactionStatus::FAILED->value,
                    TransactionStatus::EXPIRED->value,
                    TransactionStatus::REFUNDED->value,
                    TransactionStatus::SETTLED->value,
                ])
                ->latest()
                ->first();

            if ($existing) {
                return ResponseService::success(
                    new PaymentResource($existing),
                    'Existing active transaction returned.',
                    ResponseCode::SUCCESS
                );
            }

            $transactionId = 'TXN-' . date('Ymd') . '-' . strtoupper(uniqid());

            $dto = CreatePaymentDTO::fromArray(
                $request->validated(),
                $client->id,
                $merchant->id,
                $transactionId
            );

            $transaction = $this->paymentService->createPayment($dto);

            return ResponseService::success(
                new PaymentResource($transaction->load('paymentMethod', 'paymentGateway')),
                'Payment Created',
                ResponseCode::CREATED
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseService::error(ResponseCode::INVALID_PAYMENT_METHOD, 'Payment method or channel not available.');
        } catch (\InvalidArgumentException $e) {
            return ResponseService::validationError(['amount' => [$e->getMessage()]]);
        } catch (\RuntimeException $e) {
            Log::error('[PaymentController] createPayment error', ['error' => $e->getMessage()]);
            return ResponseService::error(ResponseCode::PAYMENT_GATEWAY_ERROR, $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[PaymentController] unexpected error', ['error' => $e->getMessage()]);
            return ResponseService::serverError(null, $e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /v1/payments — List Payments
    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $apiKey = $request->get('api_key_record');
        $client = $apiKey->client;

        $query = Transaction::with(['paymentMethod', 'paymentGateway'])
            ->where('client_id', $client->id)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->whereHas('paymentMethod', fn($q) =>
                $q->where('method_type', $request->payment_method)
            );
        }

        // merchant_code filter: filter by specific outlet/agent
        if ($request->filled('merchant_code')) {
            $merchant = Merchant::where('client_id', $client->id)
                ->where('merchant_code', $request->merchant_code)
                ->first();
            if ($merchant) {
                $query->where('merchant_id', $merchant->id);
            }
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) =>
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhere('merchant_ref', 'like', "%{$search}%")
            );
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        return ResponseService::paginationWithResource($paginator, PaymentResource::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /v1/payments/{id} — Get Payment Detail
    // ─────────────────────────────────────────────────────────────────────────

    public function show(Request $request, string $transactionId): JsonResponse
    {
        $apiKey = $request->get('api_key_record');
        $client = $apiKey->client;

        $transaction = Transaction::with(['paymentMethod', 'paymentGateway', 'refunds', 'merchant'])
            ->where('client_id', $client->id)
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return ResponseService::error(ResponseCode::TRANSACTION_NOT_FOUND);
        }

        return ResponseService::success(new PaymentResource($transaction));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/payments/{id}/cancel — Cancel Payment
    // ─────────────────────────────────────────────────────────────────────────

    public function cancel(Request $request, string $transactionId): JsonResponse
    {
        $apiKey = $request->get('api_key_record');
        $client = $apiKey->client;

        $transaction = Transaction::with('paymentGateway')
            ->where('client_id', $client->id)
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return ResponseService::error(ResponseCode::TRANSACTION_NOT_FOUND);
        }

        if ($transaction->status !== TransactionStatus::PENDING) {
            return ResponseService::error(ResponseCode::PAYMENT_PENDING, 'Only pending payments can be cancelled.');
        }

        try {
            $this->paymentService->cancelPayment($transaction);
            return ResponseService::success(['transaction_id' => $transactionId, 'status' => 'cancelled'], 'Payment cancelled successfully.');
        } catch (\RuntimeException $e) {
            return ResponseService::error(ResponseCode::PAYMENT_GATEWAY_ERROR, $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /v1/payments/{id}/refund — Refund Payment
    // ─────────────────────────────────────────────────────────────────────────

    public function refund(RefundPaymentRequest $request, string $transactionId): JsonResponse
    {
        return ResponseService::error(ResponseCode::REFUND_NOT_AVAILABLE, __('messages.refund_feature_unavailable'));

        $apiKey = $request->get('api_key_record');
        $client = $apiKey->client;

        $transaction = Transaction::with(['paymentGateway'])
            ->where('client_id', $client->id)
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return ResponseService::error(ResponseCode::TRANSACTION_NOT_FOUND);
        }

        if ($transaction->status !== TransactionStatus::PAID) {
            return ResponseService::error(
                ResponseCode::REFUND_NOT_AVAILABLE,
                __('messages.refund_not_available_status', ['status' => $transaction->status->value])
            );
        }

        if (!$transaction->canBeRefunded()) {
            return ResponseService::error(ResponseCode::REFUND_ALREADY_REFUNDED);
        }

        $amount = $request->input('amount')
            ? (float) $request->input('amount')
            : $transaction->getRemainingRefundableAmount();

        if ($amount > $transaction->getRemainingRefundableAmount()) {
            return ResponseService::validationError([
                'amount' => ['Refund amount (' . $amount . ') exceeds refundable amount (' . $transaction->getRemainingRefundableAmount() . ').'],
            ]);
        }

        try {
            $refund = $this->paymentService->refund(
                $transaction,
                $amount,
                $request->reason,
                $request->ref_id,
            );

            return ResponseService::success([
                'refund_id'      => $refund->refund_id,
                'transaction_id' => $transactionId,
                'refund_amount'  => (float) $refund->amount,
                'status'         => $refund->status->value,
                'reason'         => $refund->reason,
                'created_at'     => $refund->created_at->toIso8601String(),
            ], 'Refund processed');
        } catch (\RuntimeException $e) {
            return ResponseService::error(ResponseCode::PAYMENT_GATEWAY_ERROR, $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helper: Resolve Merchant (Outlet/Agent of Client)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resolve which merchant/outlet processes this payment.
     *
     * Hierarchy: Client (mitra) → HeadQuarter (regional) → Merchant (outlet/agent)
     *
     * If merchant_code provided: use that specific outlet (must belong to client).
     * If not provided: use client's first active outlet (ordered by id).
     */
    private function resolveMerchant(Client $client, ?string $merchantCode): ?Merchant
    {
        $query = Merchant::where('client_id', $client->id)
            ->where('status', 'active');

        if ($merchantCode) {
            return $query->where('merchant_code', $merchantCode)->first();
        }

        return $query->orderBy('id')->first();
    }
}
