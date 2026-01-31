<?php

return [
    // Success Messages
    'success' => 'Operation completed successfully',
    'resource_created' => 'Resource created successfully',
    'request_accepted' => 'Request accepted',
    'no_content' => 'No content',
    'resource_updated' => 'Resource updated successfully',
    'resource_deleted' => 'Resource deleted successfully',

    // Error Messages
    'error' => 'An error occurred',
    'not_found' => 'Resource not found',
    'resource_not_found' => 'Resource not found',
    'endpoint_not_found' => 'Endpoint not found',
    'unauthorized' => 'Unauthorized access',
    'forbidden' => 'Access forbidden',
    'validation_failed' => 'Validation failed',
    'too_many_requests' => 'Too many requests. Please try again later.',
    'method_not_allowed' => 'Method not allowed',

    // Validation Errors
    'validation_error' => 'Validation failed',
    'invalid_input' => 'Invalid input data',
    'required_field' => 'This field is required',
    'invalid_format' => 'Invalid format',
    'duplicate_entry' => 'Duplicate entry found',

    // Authentication Errors
    'auth_failed' => 'Authentication failed',
    'token_expired' => 'Token has expired',
    'token_invalid' => 'Invalid token',
    'session_expired' => 'Session has expired',

    // API Key Errors
    'invalid_api_key' => 'Invalid API key',
    'invalid_api_secret' => 'Invalid API secret',
    'api_key_revoked' => 'API key has been revoked',
    'api_key_expired' => 'API key has expired',
    'api_key_required' => 'API key is required',
    'ip_not_allowed' => 'IP address not allowed',
    'invalid_signature' => 'Invalid signature',
    'request_expired' => 'Request has expired',
    'invalid_timestamp' => 'Invalid timestamp',

    // API Key Management
    'api_keys_retrieved' => 'API keys retrieved successfully',
    'api_key_retrieved' => 'API key retrieved successfully',
    'api_key_created' => 'API key created successfully',
    'api_key_updated' => 'API key updated successfully',
    'api_key_revoked' => 'API key revoked successfully',
    'api_key_status_toggled' => 'API key status toggled successfully',
    'api_secret_regenerated' => 'API secret regenerated successfully',
    'client_api_keys_retrieved' => 'Client API keys retrieved successfully',
    'api_key_not_found' => 'API key not found',
    'api_key_create_error' => 'Failed to create API key',
    'api_key_update_error' => 'Failed to update API key',
    'api_key_revoke_error' => 'Failed to revoke API key',
    'api_secret_regenerate_error' => 'Failed to regenerate API secret',
    'api_key_toggle_error' => 'Failed to toggle API key status',
    'api_keys_retrieve_error' => 'Failed to retrieve API keys',

    // Audit Logs
    'audit_logs_retrieved' => 'Audit logs retrieved successfully',
    'audit_log_retrieved' => 'Audit log retrieved successfully',
    'audit_log_not_found' => 'Audit log not found',
    'audit_logs_error' => 'Failed to retrieve audit logs',

    // Entity Not Found
    'user_not_found' => 'User not found',
    'client_not_found' => 'Client not found',
    'transaction_not_found' => 'Transaction not found',
    'merchant_not_found' => 'Merchant not found',

    // Business Logic Errors
    'insufficient_balance' => 'Insufficient balance',
    'invalid_payment_method' => 'Invalid payment method',
    'payment_failed' => 'Payment failed',
    'payment_expired' => 'Payment has expired',
    'payment_pending' => 'Payment is pending',
    'transaction_failed' => 'Transaction failed',
    'transaction_expired' => 'Transaction has expired',
    'invalid_amount' => 'Invalid amount',
    'invalid_currency' => 'Invalid currency',
    'duplicate_transaction' => 'Duplicate transaction',
    'client_kyb_pending' => 'Client KYB is pending approval',
    'client_kyb_rejected' => 'Client KYB rejected',
    'client_suspended' => 'Client account is suspended',
    'merchant_suspended' => 'Merchant account is suspended',
    'settlement_failed' => 'Settlement failed',
    'reconciliation_failed' => 'Reconciliation failed',

    // Server Errors
    'internal_server_error' => 'Internal server error',
    'database_error' => 'Database error',
    'external_service_error' => 'External service error',
    'payment_gateway_error' => 'Payment gateway error',
    'network_error' => 'Network error',
    'service_unavailable' => 'Service unavailable',

    // Validation
    'client_id_required' => 'The client ID field is required',
    'key_name_required' => 'The key name field is required',
    'environment_required' => 'The environment field is required',
    'environment_invalid' => 'The environment must be dev, staging, or production',
    'ip_whitelist_invalid' => 'The IP whitelist contains invalid IP addresses',
    'key_name_string' => 'The key name must be a string',

    // Auth
    'login_success' => 'Login successful',
    'login_failed' => 'Login failed',
    'login_error' => 'An error occurred during login',
    'logout_success' => 'Logout successful',
    'logout_error' => 'An error occurred during logout',
    'logout_all_success' => 'Logged out from all devices successfully',
    'logout_all_error' => 'Failed to logout from all devices',
    'refresh_success' => 'Token refreshed successfully',
    'refresh_failed' => 'Failed to refresh token',
    'refresh_error' => 'An error occurred while refreshing token',
    'profile_retrieved' => 'Profile retrieved successfully',
    'tokens_retrieved' => 'Tokens retrieved successfully',
    'tokens_error' => 'Failed to retrieve tokens',
    'api_login_success' => 'API login successful',
    'api_login_failed' => 'API login failed',
    'api_login_error' => 'An error occurred during API login',
    'api_logout_success' => 'API logout successful',
    'api_logout_error' => 'An error occurred during API logout',
    'api_profile_retrieved' => 'API profile retrieved successfully',
];
