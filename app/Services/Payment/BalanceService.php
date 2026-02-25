<?php

namespace App\Services\Payment;

use App\Enums\BalanceTransactionType;
use App\Models\Client;
use App\Models\ClientBalance;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BalanceService
{
    /**
     * Called when a transaction is marked PAID.
     * Net amount goes into pending_balance — awaiting settlement.
     */
    public function recordPayment(Transaction $transaction): void
    {
        $client = $transaction->client ?? Client::find($transaction->client_id);

        if (!$client) {
            Log::warning('[BalanceService] Client not found for transaction', [
                'transaction_id' => $transaction->transaction_id,
            ]);
            return;
        }

        $netAmount = (float) $transaction->net_amount;

        DB::transaction(function () use ($client, $transaction, $netAmount) {
            // Lock client row to prevent race conditions
            $client = Client::lockForUpdate()->find($client->id);

            $pendingBefore   = (float) $client->pending_balance;
            $availableBefore = (float) $client->available_balance;

            $client->increment('pending_balance', $netAmount);

            ClientBalance::create([
                'client_id'               => $client->id,
                'transaction_type'        => BalanceTransactionType::PAYMENT,
                'amount'                  => $netAmount,
                'available_balance_before' => $availableBefore,
                'available_balance_after'  => $availableBefore,
                'pending_balance_before'   => $pendingBefore,
                'pending_balance_after'    => $pendingBefore + $netAmount,
                'minus_balance_before'     => (float) $client->minus_balance,
                'minus_balance_after'      => (float) $client->minus_balance,
                'reference_type'           => 'transaction',
                'reference_id'             => $transaction->id,
                'description'              => "Payment received for transaction {$transaction->transaction_id}",
            ]);
        });

        Log::info('[BalanceService] Pending balance updated (payment)', [
            'transaction_id' => $transaction->transaction_id,
            'net_amount'     => $netAmount,
            'client_id'      => $client->id,
        ]);
    }

    /**
     * Called when a refund is processed.
     * Deducts from available_balance (if settled) or pending_balance (if still pending settlement).
     */
    public function recordRefund(Transaction $transaction, float $refundAmount): void
    {
        $client = $transaction->client ?? Client::find($transaction->client_id);

        if (!$client) {
            Log::warning('[BalanceService] Client not found for refund', [
                'transaction_id' => $transaction->transaction_id,
            ]);
            return;
        }

        DB::transaction(function () use ($client, $transaction, $refundAmount) {
            $client = Client::lockForUpdate()->find($client->id);

            $availableBefore = (float) $client->available_balance;
            $pendingBefore   = (float) $client->pending_balance;

            // Deduct from available first, then pending if needed
            $fromAvailable = min($refundAmount, $availableBefore);
            $fromPending   = $refundAmount - $fromAvailable;

            if ($fromAvailable > 0) {
                $client->decrement('available_balance', $fromAvailable);
            }
            if ($fromPending > 0) {
                $client->decrement('pending_balance', $fromPending);
            }

            $client->refresh();

            ClientBalance::create([
                'client_id'               => $client->id,
                'transaction_type'        => BalanceTransactionType::ADJUSTMENT,
                'amount'                  => -$refundAmount,
                'available_balance_before' => $availableBefore,
                'available_balance_after'  => (float) $client->available_balance,
                'pending_balance_before'   => $pendingBefore,
                'pending_balance_after'    => (float) $client->pending_balance,
                'minus_balance_before'     => (float) $client->minus_balance,
                'minus_balance_after'      => (float) $client->minus_balance,
                'reference_type'           => 'transaction',
                'reference_id'             => $transaction->id,
                'description'              => "Refund of {$refundAmount} for transaction {$transaction->transaction_id}",
            ]);
        });

        Log::info('[BalanceService] Balance deducted (refund)', [
            'transaction_id' => $transaction->transaction_id,
            'refund_amount'  => $refundAmount,
            'client_id'      => $client->id,
        ]);
    }
}
