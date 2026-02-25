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
        'merchant_ref',
        'currency',
        'pg_reference_id',
        'pg_checkout_url',
        'pg_deeplink_url',
        'pg_qr_string',
        'pg_va_number',
        'pos_reference_id',
        'payment_method_id',
        'payment_gateway_id',
        'gross_amount',
        'vendor_margin',
        'our_margin',
        'mdr_amount',
        'net_amount',
        'refunded_amount',
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
        'callback_url',
        'redirect_url',
        'items',
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
        'refunded_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'settlement_date' => 'date',
        'is_overridden' => 'boolean',
        'overridden_at' => 'datetime',
        'metadata' => 'array',
        'items' => 'array',
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

    public function refunds()
    {
        return $this->hasMany(PaymentRefund::class);
    }

    public function webhookLogs()
    {
        return $this->hasMany(PaymentWebhookLog::class);
    }

    public function gatewayLogs()
    {
        return $this->hasMany(PaymentGatewayLog::class);
    }

    public function canBeRefunded(): bool
    {
        return $this->status === TransactionStatus::PAID
            && (float) $this->refunded_amount < (float) $this->gross_amount;
    }

    public function getRemainingRefundableAmount(): float
    {
        return (float) $this->gross_amount - (float) $this->refunded_amount;
    }
}
