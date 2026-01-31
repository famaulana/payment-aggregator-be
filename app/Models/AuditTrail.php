<?php

namespace App\Models;

use App\Enums\AuditActionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_role',
        'client_id',
        'action_type',
        'entity_type',
        'entity_id',
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
        'action_type' => AuditActionType::class,
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
