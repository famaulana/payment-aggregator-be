<?php

namespace App\Models;

use App\Enums\BalanceTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'transaction_type',
        'amount',
        'available_balance_before',
        'available_balance_after',
        'pending_balance_before',
        'pending_balance_after',
        'minus_balance_before',
        'minus_balance_after',
        'reference_type',
        'reference_id',
        'description',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_type' => BalanceTransactionType::class,
        'amount' => 'decimal:2',
        'available_balance_before' => 'decimal:2',
        'available_balance_after' => 'decimal:2',
        'pending_balance_before' => 'decimal:2',
        'pending_balance_after' => 'decimal:2',
        'minus_balance_before' => 'decimal:2',
        'minus_balance_after' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
