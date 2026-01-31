<?php

namespace App\Enums;

enum BusinessType: string
{
    case PT = 'pt';
    case CV = 'cv';
    case FIRMA = 'firma';
    case UD = 'ud';
    case KOPERASI = 'koperasi';
    case YAYASAN = 'yayasan';
    case PERORANGAN = 'perorangan';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PT => 'PT (Perseroan Terbatas)',
            self::CV => 'CV (Commanditaire Vennootschap)',
            self::FIRMA => 'Firma',
            self::UD => 'UD (Usaha Dagang)',
            self::KOPERASI => 'Koperasi',
            self::YAYASAN => 'Yayasan',
            self::PERORANGAN => 'Perorangan',
            self::OTHER => 'Lainnya',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
