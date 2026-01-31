<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReconciliationBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_code',
        'period_start_date',
        'period_end_date',
        'reconciliation_date',
        'internal_file_path',
        'pg_file_path',
        'total_matched',
        'total_unmatched',
        'total_disputed',
        'total_amount_discrepancy',
        'status',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'reconciliation_date' => 'date',
        'total_matched' => 'integer',
        'total_unmatched' => 'integer',
        'total_disputed' => 'integer',
        'total_amount_discrepancy' => 'decimal:2',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reconciliations()
    {
        return $this->hasMany(Reconciliation::class);
    }
}
