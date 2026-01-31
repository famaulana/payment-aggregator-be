<?php

namespace App\Models;

use App\Enums\NotificationType;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notification_type',
        'title',
        'message',
        'data',
        'reference_type',
        'reference_id',
        'status',
        'fcm_message_id',
        'fcm_response',
        'is_read',
        'read_at',
        'sent_at',
    ];

    protected $casts = [
        'notification_type' => NotificationType::class,
        'status' => NotificationStatus::class,
        'data' => 'array',
        'fcm_response' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
