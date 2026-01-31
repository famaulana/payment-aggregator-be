<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'code',
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function subDistricts()
    {
        return $this->hasMany(SubDistrict::class);
    }

    public function headOffices()
    {
        return $this->hasMany(HeadOffice::class);
    }

    public function merchants()
    {
        return $this->hasMany(Merchant::class);
    }
}
