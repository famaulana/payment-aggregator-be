<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\SettlementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'merchant_id',
        'head_quarter_id',
        'transaction_id',
        'pg_reference_id',
        'pos_reference_id',
        'payment_method_id',
        'payment_gateway_id',
        'gross_amount',
        'vendor_margin',
        'our_margin',
        'mdr_amount',
        'net_amount',
        'status',
        'original_status',
        'paid_at',
        'expired_at',
        'payment_type',
        'account_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'settlement_status',
        'settlement_date',
        'settlement_batch_id',
        'is_overridden',
        'overridden_by',
        'overridden_at',
        'override_reason',
        'description',
        'metadata',
    ];

    protected $casts = [
        'status' => TransactionStatus::class,
        'original_status' => TransactionStatus::class,
        'settlement_status' => SettlementStatus::class,
        'gross_amount' => 'decimal:2',
        'vendor_margin' => 'decimal:2',
        'our_margin' => 'decimal:2',
        'mdr_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'settlement_date' => 'date',
        'is_overridden' => 'boolean',
        'overridden_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function headQuarter()
    {
        return $this->belongsTo(HeadQuarter::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function settlementBatch()
    {
        return $this->belongsTo(SettlementBatch::class);
    }

    public function overriddenBy()
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }

    public function settlementTransactions()
    {
        return $this->hasMany(SettlementTransaction::class);
    }

    public function reconciliations()
    {
        return $this->hasMany(Reconciliation::class);
    }
}
