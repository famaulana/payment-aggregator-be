<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubDistrict extends Model
{
    use HasFactory;

    protected $fillable = [
        'district_id',
        'code',
        'name',
        'postal_code',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function district()
    {
        return $this->belongsTo(District::class);
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
