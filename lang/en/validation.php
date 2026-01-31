<?php

return [
    // Custom validation messages for API requests
    'email_required' => 'The email field is required',
    'email_invalid' => 'The email must be a valid email address',
    'password_required' => 'The password field is required',
    'password_min' => 'The password must be at least 8 characters',
    'refresh_token_required' => 'The refresh token field is required',
    'api_key_required' => 'The API key field is required',
    'api_key_not_found' => 'The API key was not found',
    'api_secret_required' => 'The API secret field is required',
    'client_id_required' => 'The client ID field is required',
    'client_not_found' => 'The client was not found',
    'key_name_required' => 'The key name field is required',
    'environment_required' => 'The environment field is required',
    'environment_invalid' => 'The environment must be dev, staging, or production',
    'ip_whitelist_invalid' => 'The IP whitelist contains invalid IP addresses',
    'key_name_string' => 'The key name must be a string',
];
