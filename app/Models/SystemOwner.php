<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemOwner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'business_type',
        'pic_name',
        'pic_position',
        'pic_phone',
        'pic_email',
        'company_phone',
        'company_email',
        'province_id',
        'city_id',
        'address',
        'postal_code',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function users()
    {
        return $this->morphMany(User::class, 'entity');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
