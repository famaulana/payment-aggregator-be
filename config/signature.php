<?php

return [
    'enabled' => env('SIGNATURE_ENABLED', true),

    'time_tolerance' => env('SIGNATURE_TIME_TOLERANCE', 300),

    'algorithm' => env('SIGNATURE_ALGORITHM', 'sha256'),

    'excluded_paths' => [
        'api/v1/login',
        'api/v1/register',
        'api/v1/health',
        'health',
        'up',
    ],

    'header_names' => [
        'signature' => 'X-Signature',
        'timestamp' => 'X-Timestamp',
        'api_key' => 'X-API-Key',
        'api_secret' => 'X-API-Secret',
    ],
];
