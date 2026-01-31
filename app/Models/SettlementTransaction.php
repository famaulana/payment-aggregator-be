<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettlementTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'settlement_batch_id',
        'transaction_id',
        'gross_amount',
        'mdr_amount',
        'net_amount',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'mdr_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function settlementBatch()
    {
        return $this->belongsTo(SettlementBatch::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
