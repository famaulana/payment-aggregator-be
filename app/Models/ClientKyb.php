<?php

namespace App\Models;

use App\Enums\BusinessType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientKyb extends Model
{
    use HasFactory;

    protected $table = 'client_kyb';

    protected $fillable = [
        'client_id',
        'business_type',
        'business_name_legal',
        'business_industry',
        'nib',
        'npwp',
        'siup',
        'tdp',
        'akta_pendirian_number',
        'akta_pendirian_date',
        'akta_pendirian_notaris',
        'sk_kemenkumham_number',
        'sk_kemenkumham_date',
        'business_province_id',
        'business_city_id',
        'business_district_id',
        'business_sub_district_id',
        'business_address',
        'business_postal_code',
        'pic_name',
        'pic_position',
        'pic_phone',
        'pic_email',
        'pic_ktp',
        'director_name',
        'director_ktp',
        'director_phone',
        'director_email',
        'doc_nib_file',
        'doc_npwp_file',
        'doc_siup_file',
        'doc_akta_pendirian_file',
        'doc_sk_kemenkumham_file',
        'doc_ktp_pic_file',
        'doc_ktp_director_file',
        'doc_bank_statement_file',
        'doc_selfie_ktp_file',
        'doc_business_photo_file',
        'doc_additional_files',
        'verified_by',
        'verified_at',
        'verification_notes',
        'metadata',
    ];

    protected $casts = [
        'business_type' => BusinessType::class,
        'akta_pendirian_date' => 'date',
        'sk_kemenkumham_date' => 'date',
        'doc_additional_files' => 'array',
        'metadata' => 'array',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function businessProvince()
    {
        return $this->belongsTo(Province::class, 'business_province_id');
    }

    public function businessCity()
    {
        return $this->belongsTo(City::class, 'business_city_id');
    }

    public function businessDistrict()
    {
        return $this->belongsTo(District::class, 'business_district_id');
    }

    public function businessSubDistrict()
    {
        return $this->belongsTo(SubDistrict::class, 'business_sub_district_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
