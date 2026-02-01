<?php

return [

    /*
    |--------------------------------------------------------------------------
    | General Success
    |--------------------------------------------------------------------------
    */
    'success' => 'Request processed successfully',
    'resource_created' => 'Resource created successfully',
    'resource_updated' => 'Resource updated successfully',
    'resource_deleted' => 'Resource deleted successfully',
    'request_accepted' => 'Request accepted',
    'no_content' => 'No content available',

    /*
    |--------------------------------------------------------------------------
    | Validation & Input
    |--------------------------------------------------------------------------
    */
    'validation_error' => 'Validation error',
    'validation_failed' => 'Validation failed',
    'invalid_input' => 'Invalid input provided',
    'required_field' => 'Required field is missing',
    'invalid_format' => 'Invalid data format',
    'duplicate_entry' => 'Duplicate entry detected',

    /*
    |--------------------------------------------------------------------------
    | Authentication & Authorization
    |--------------------------------------------------------------------------
    */
    'unauthorized' => 'Unauthorized access',
    'auth_failed' => 'Authentication failed',
    'forbidden' => 'Access forbidden',
    'session_expired' => 'Session has expired',

    'token_expired' => 'Token has expired',
    'token_invalid' => 'Invalid token',

    /*
    |--------------------------------------------------------------------------
    | API Key & Signature
    |--------------------------------------------------------------------------
    */
    'api_key_required' => 'API key is required',
    'invalid_api_key' => 'Invalid API key',
    'invalid_api_secret' => 'Invalid API secret',
    'api_key_revoked' => 'API key has been revoked',
    'api_key_expired' => 'API key has expired',
    'ip_not_allowed' => 'IP address not allowed',
    'invalid_signature' => 'Invalid request signature',
    'request_expired' => 'Request has expired',
    'invalid_timestamp' => 'Invalid request timestamp',

    /*
    |--------------------------------------------------------------------------
    | Resource Not Found
    |--------------------------------------------------------------------------
    */
    'not_found' => 'Resource not found',
    'resource_not_found' => 'Requested resource not found',
    'endpoint_not_found' => 'Endpoint not found',

    'user_not_found' => 'User not found',
    'client_not_found' => 'Client not found',
    'transaction_not_found' => 'Transaction not found',
    'merchant_not_found' => 'Merchant not found',
    'api_key_not_found' => 'API key not found',
    'audit_log_not_found' => 'Audit log not found',

    /*
    |--------------------------------------------------------------------------
    | API Key Management (Dashboard)
    |--------------------------------------------------------------------------
    */
    'api_keys_retrieved' => 'API keys retrieved successfully',
    'api_keys_retrieve_error' => 'Failed to retrieve API keys',

    'api_key_created' => 'API key created successfully',
    'api_key_create_error' => 'Failed to create API key',

    'api_key_retrieved' => 'API key retrieved successfully',
    'api_key_updated' => 'API key updated successfully',
    'api_key_update_error' => 'Failed to update API key',

    'api_key_revoked' => 'API key revoked successfully',
    'api_key_revoke_error' => 'Failed to revoke API key',

    'api_secret_regenerated' => 'API secret regenerated successfully',
    'api_secret_regenerate_error' => 'Failed to regenerate API secret',

    'api_key_status_toggled' => 'API key status updated successfully',
    'api_key_toggle_error' => 'Failed to update API key status',

    'client_api_keys_retrieved' => 'Client API keys retrieved successfully',

    /*
    |--------------------------------------------------------------------------
    | Audit Logs
    |--------------------------------------------------------------------------
    */
    'audit_logs_retrieved' => 'Audit logs retrieved successfully',
    'audit_logs_error' => 'Failed to retrieve audit logs',
    'audit_log_retrieved' => 'Audit log retrieved successfully',

    /*
    |--------------------------------------------------------------------------
    | Payment & Transaction
    |--------------------------------------------------------------------------
    */
    'insufficient_balance' => 'Insufficient balance',
    'invalid_payment_method' => 'Invalid payment method',
    'payment_failed' => 'Payment failed',
    'payment_expired' => 'Payment expired',
    'payment_pending' => 'Payment pending',

    'transaction_failed' => 'Transaction failed',
    'transaction_expired' => 'Transaction expired',
    'duplicate_transaction' => 'Duplicate transaction detected',

    'invalid_amount' => 'Invalid amount',
    'invalid_currency' => 'Invalid currency',

    /*
    |--------------------------------------------------------------------------
    | Client & Merchant Status
    |--------------------------------------------------------------------------
    */
    'client_kyb_pending' => 'Client KYB verification pending',
    'client_kyb_rejected' => 'Client KYB verification rejected',
    'client_suspended' => 'Client account is suspended',
    'merchant_suspended' => 'Merchant account is suspended',

    /*
    |--------------------------------------------------------------------------
    | Settlement & Reconciliation
    |--------------------------------------------------------------------------
    */
    'settlement_failed' => 'Settlement failed',
    'reconciliation_failed' => 'Reconciliation failed',

    /*
    |--------------------------------------------------------------------------
    | System Errors
    |--------------------------------------------------------------------------
    */
    'internal_server_error' => 'Internal server error',
    'database_error' => 'Database error occurred',
    'external_service_error' => 'External service error',
    'payment_gateway_error' => 'Payment gateway error',
    'network_error' => 'Network error occurred',
    'too_many_requests' => 'Too many requests',
    'service_unavailable' => 'Service unavailable',

    /*
    |--------------------------------------------------------------------------
    | Additional Messages
    |--------------------------------------------------------------------------
    */
    'unauthorized_action' => 'Unauthorized action',
    'method_not_allowed' => 'Method not allowed',
];
