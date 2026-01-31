<?php

namespace App\Models;

use App\Enums\FloatingFundMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FloatingFund extends Model
{
    use HasFactory;

    protected $fillable = [
        'movement_type',
        'amount',
        'settlement_batch_id',
        'balance_before',
        'balance_after',
        'total_allocated',
        'total_used',
        'available_balance',
        'description',
        'reference_number',
        'created_by',
    ];

    protected $casts = [
        'movement_type' => FloatingFundMovementType::class,
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'total_allocated' => 'decimal:2',
        'total_used' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function settlementBatch()
    {
        return $this->belongsTo(SettlementBatch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
