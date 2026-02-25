<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Passport Guard
    |--------------------------------------------------------------------------
    |
    | Here you may specify which authentication guard Passport will use when
    | authenticating users. This value should correspond with one of your
    | guards that is already present in your "auth" configuration file.
    |
    */

    'guard' => 'api',

    /*
    |--------------------------------------------------------------------------
    | Encryption Keys
    |--------------------------------------------------------------------------
    |
    | Passport uses encryption keys while generating secure access tokens for
    | your application. By default, the keys are stored as local files but
    | can be set via environment variables when that is more convenient.
    |
    */

    'private_key' => env('PASSPORT_PRIVATE_KEY', storage_path('oauth-private.key')),
    'public_key' => env('PASSPORT_PUBLIC_KEY', storage_path('oauth-public.key')),

    /*
    |--------------------------------------------------------------------------
    | Passport Database Connection
    |--------------------------------------------------------------------------
    |
    | By default, Passport's models will utilize your application's default
    | database connection. If you wish to use a different connection you
    | may specify the configured name of the database connection here.
    |
    */

    'connection' => env('PASSPORT_CONNECTION'),
    'dashboard_access_token_ttl'  => env('PASSPORT_DASHBOARD_ACCESS_TOKEN_TTL', 60),   // menit
    'dashboard_refresh_token_ttl' => env('PASSPORT_DASHBOARD_REFRESH_TOKEN_TTL', 4320), // menit (default 3 hari)
    'api_access_token_ttl'        => env('PASSPORT_API_ACCESS_TOKEN_TTL', 60),          // menit

    'password_client' => [
        'id' => env('PASSPORT_PASSWORD_CLIENT_ID'),
        'secret' => env('PASSPORT_PASSWORD_CLIENT_SECRET'),
    ],

    'dashboard_client' => [
        'id' => env('PASSPORT_DASHBOARD_CLIENT_ID'),
        'secret' => env('PASSPORT_DASHBOARD_CLIENT_SECRET'),
    ],

    'api_server_client' => [
        'id' => env('PASSPORT_API_SERVER_CLIENT_ID'),
        'secret' => env('PASSPORT_API_SERVER_CLIENT_SECRET'),
    ],

    'personal_access_client' => [
        'id' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID'),
        'secret' => env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET'),
    ],
];
