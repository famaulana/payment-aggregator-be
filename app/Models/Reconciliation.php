<?php

namespace App\Models;

use App\Enums\ReconciliationMatchType;
use App\Enums\ReconciliationMatchStatus;
use App\Enums\ResolutionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reconciliation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reconciliation_batch_id',
        'transaction_id',
        'internal_transaction_id',
        'internal_amount',
        'internal_status',
        'internal_settlement_date',
        'pg_reference_id',
        'pg_amount',
        'pg_status',
        'pg_settlement_date',
        'match_type',
        'match_status',
        'amount_discrepancy',
        'status_match',
        'date_match',
        'resolved',
        'resolution_type',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'client_debt_amount',
        'client_debt_settled',
        'client_debt_settled_at',
    ];

    protected $casts = [
        'match_type' => ReconciliationMatchType::class,
        'match_status' => ReconciliationMatchStatus::class,
        'resolution_type' => ResolutionType::class,
        'internal_amount' => 'decimal:2',
        'pg_amount' => 'decimal:2',
        'amount_discrepancy' => 'decimal:2',
        'internal_settlement_date' => 'date',
        'pg_settlement_date' => 'date',
        'status_match' => 'boolean',
        'date_match' => 'boolean',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'client_debt_amount' => 'decimal:2',
        'client_debt_settled' => 'boolean',
        'client_debt_settled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function reconciliationBatch()
    {
        return $this->belongsTo(ReconciliationBatch::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
