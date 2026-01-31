<?php

namespace App\Models;

use App\Enums\AuditActionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditTrail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_role',
        'action_type',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'changes_summary',
        'ip_address',
        'user_agent',
        'endpoint',
        'http_method',
        'notes',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForRole($query, $role)
    {
        return $query->where('user_role', $role);
    }

    public function scopeForAction($query, $action)
    {
        return $query->where('action_type', $action);
    }

    public function scopeForEntity($query, $entityType, $entityId)
    {
        return $query->where('auditable_type', $entityType)
                    ->where('auditable_id', $entityId);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
