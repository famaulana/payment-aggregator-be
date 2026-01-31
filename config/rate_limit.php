<?php

return [
    'max_attempts' => env('RATE_LIMIT_MAX_ATTEMPTS', 60),

    'decay_minutes' => env('RATE_LIMIT_DECAY_MINUTES', 1),

    'per_minute' => [
        'default' => 60,
        'strict' => 20,
        'loose' => 100,
    ],

    'per_hour' => [
        'default' => 1000,
        'strict' => 500,
        'loose' => 2000,
    ],

    'per_day' => [
        'default' => 10000,
        'strict' => 5000,
        'loose' => 20000,
    ],

    'by_role' => [
        'admin' => 10000,
        'client' => 1000,
        'merchant' => 500,
    ],
];
