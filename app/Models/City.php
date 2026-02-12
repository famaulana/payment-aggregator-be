<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'province_id',
        'code',
        'name',
        'type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function districts()
    {
        return $this->hasMany(District::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function headQuarters()
    {
        return $this->hasMany(HeadQuarter::class);
    }

    public function merchants()
    {
        return $this->hasMany(Merchant::class);
    }
}
