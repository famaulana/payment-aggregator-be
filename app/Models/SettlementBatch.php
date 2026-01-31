<?php

namespace App\Models;

use App\Enums\SettlementBatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettlementBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'batch_code',
        'period_start_date',
        'period_end_date',
        'settlement_date',
        'total_transactions',
        'total_gross_amount',
        'total_mdr_amount',
        'total_net_amount',
        'floating_fund_used',
        'floating_fund_repaid',
        'floating_fund_balance',
        'previous_mismatch_adjustment',
        'final_settlement_amount',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'payout_method',
        'payout_reference',
        'payout_processed_by',
        'payout_processed_at',
        'payout_notes',
    ];

    protected $casts = [
        'status' => SettlementBatchStatus::class,
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'settlement_date' => 'date',
        'total_gross_amount' => 'decimal:2',
        'total_mdr_amount' => 'decimal:2',
        'total_net_amount' => 'decimal:2',
        'floating_fund_used' => 'decimal:2',
        'floating_fund_repaid' => 'decimal:2',
        'floating_fund_balance' => 'decimal:2',
        'previous_mismatch_adjustment' => 'decimal:2',
        'final_settlement_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'payout_processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function payoutProcessor()
    {
        return $this->belongsTo(User::class, 'payout_processed_by');
    }

    public function settlementTransactions()
    {
        return $this->hasMany(SettlementTransaction::class);
    }

    public function transactions()
    {
        return $this->hasManyThrough(Transaction::class, SettlementTransaction::class, 'settlement_batch_id', 'id', 'id', 'transaction_id');
    }

    public function floatingFunds()
    {
        return $this->hasMany(FloatingFund::class);
    }
}
