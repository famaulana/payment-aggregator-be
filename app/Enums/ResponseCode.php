<?php

namespace App\Enums;

enum ResponseCode: string
{
    // Success Codes - 0000-0999
    case SUCCESS = '0000';
    case CREATED = '0001';
    case ACCEPTED = '0002';
    case NO_CONTENT = '0003';
    case UPDATED = '0004';
    case DELETED = '0005';

    // Validation Error Codes - 1000-1999
    case VALIDATION_ERROR = '1000';
    case INVALID_INPUT = '1001';
    case MISSING_REQUIRED_FIELD = '1002';
    case INVALID_FORMAT = '1003';
    case DUPLICATE_ENTRY = '1004';

    // Authentication & Authorization Error Codes - 2000-2999
    case UNAUTHORIZED = '2000';
    case AUTHENTICATION_FAILED = '2001';
    case TOKEN_EXPIRED = '2002';
    case TOKEN_INVALID = '2003';
    case FORBIDDEN = '2004';
    case SESSION_EXPIRED = '2005';

    // Business Logic Error Codes - 3000-3999
    case USER_NOT_FOUND = '3000';
    case CLIENT_NOT_FOUND = '3001';
    case TRANSACTION_NOT_FOUND = '3002';
    case MERCHANT_NOT_FOUND = '3003';
    case INSUFFICIENT_BALANCE = '3004';
    case INVALID_PAYMENT_METHOD = '3005';
    case PAYMENT_FAILED = '3006';
    case PAYMENT_EXPIRED = '3007';
    case PAYMENT_PENDING = '3008';
    case TRANSACTION_FAILED = '3009';
    case TRANSACTION_EXPIRED = '3010';
    case INVALID_AMOUNT = '3011';
    case INVALID_CURRENCY = '3012';
    case DUPLICATE_TRANSACTION = '3013';
    case KYB_PENDING = '3014';
    case KYB_REJECTED = '3015';
    case CLIENT_SUSPENDED = '3016';
    case MERCHANT_SUSPENDED = '3017';
    case SETTLEMENT_FAILED = '3018';
    case RECONCILIATION_FAILED = '3019';

    // Server Error Codes - 5000-5999
    case INTERNAL_SERVER_ERROR = '5000';
    case DATABASE_ERROR = '5001';
    case EXTERNAL_SERVICE_ERROR = '5002';
    case PAYMENT_GATEWAY_ERROR = '5003';
    case NETWORK_ERROR = '5004';
    case TOO_MANY_REQUESTS = '5005';
    case SERVICE_UNAVAILABLE = '5006';

    // Resource Not Found - 4000-4999
    case NOT_FOUND = '4000';
    case RESOURCE_NOT_FOUND = '4001';
    case ENDPOINT_NOT_FOUND = '4002';

    public function getMessage(): string
    {
        return match($this) {
            self::SUCCESS => 'messages.success',
            self::CREATED => 'messages.resource_created',
            self::ACCEPTED => 'messages.request_accepted',
            self::NO_CONTENT => 'messages.no_content',
            self::UPDATED => 'messages.resource_updated',
            self::DELETED => 'messages.resource_deleted',

            self::VALIDATION_ERROR => 'messages.validation_error',
            self::INVALID_INPUT => 'messages.invalid_input',
            self::MISSING_REQUIRED_FIELD => 'messages.required_field',
            self::INVALID_FORMAT => 'messages.invalid_format',
            self::DUPLICATE_ENTRY => 'messages.duplicate_entry',

            self::UNAUTHORIZED => 'messages.unauthorized',
            self::AUTHENTICATION_FAILED => 'messages.auth_failed',
            self::TOKEN_EXPIRED => 'messages.token_expired',
            self::TOKEN_INVALID => 'messages.token_invalid',
            self::FORBIDDEN => 'messages.forbidden',
            self::SESSION_EXPIRED => 'messages.session_expired',

            self::USER_NOT_FOUND => 'messages.user_not_found',
            self::CLIENT_NOT_FOUND => 'messages.client_not_found',
            self::TRANSACTION_NOT_FOUND => 'messages.transaction_not_found',
            self::MERCHANT_NOT_FOUND => 'messages.merchant_not_found',
            self::INSUFFICIENT_BALANCE => 'messages.insufficient_balance',
            self::INVALID_PAYMENT_METHOD => 'messages.invalid_payment_method',
            self::PAYMENT_FAILED => 'messages.payment_failed',
            self::PAYMENT_EXPIRED => 'messages.payment_expired',
            self::PAYMENT_PENDING => 'messages.payment_pending',
            self::TRANSACTION_FAILED => 'messages.transaction_failed',
            self::TRANSACTION_EXPIRED => 'messages.transaction_expired',
            self::INVALID_AMOUNT => 'messages.invalid_amount',
            self::INVALID_CURRENCY => 'messages.invalid_currency',
            self::DUPLICATE_TRANSACTION => 'messages.duplicate_transaction',
            self::KYB_PENDING => 'messages.client_kyb_pending',
            self::KYB_REJECTED => 'messages.client_kyb_rejected',
            self::CLIENT_SUSPENDED => 'messages.client_suspended',
            self::MERCHANT_SUSPENDED => 'messages.merchant_suspended',
            self::SETTLEMENT_FAILED => 'messages.settlement_failed',
            self::RECONCILIATION_FAILED => 'messages.reconciliation_failed',

            self::INTERNAL_SERVER_ERROR => 'messages.internal_server_error',
            self::DATABASE_ERROR => 'messages.database_error',
            self::EXTERNAL_SERVICE_ERROR => 'messages.external_service_error',
            self::PAYMENT_GATEWAY_ERROR => 'messages.payment_gateway_error',
            self::NETWORK_ERROR => 'messages.network_error',
            self::TOO_MANY_REQUESTS => 'messages.too_many_requests',
            self::SERVICE_UNAVAILABLE => 'messages.service_unavailable',

            self::NOT_FOUND => 'messages.not_found',
            self::RESOURCE_NOT_FOUND => 'messages.resource_not_found',
            self::ENDPOINT_NOT_FOUND => 'messages.endpoint_not_found',
        };
    }

    public function getHttpStatusCode(): int
    {
        return match($this) {
            // Success - 2xx
            self::SUCCESS => 200,
            self::CREATED => 201,
            self::ACCEPTED => 202,
            self::NO_CONTENT => 204,
            self::UPDATED => 200,
            self::DELETED => 200,

            // Validation Error - 4xx
            self::VALIDATION_ERROR, self::INVALID_INPUT,
            self::MISSING_REQUIRED_FIELD, self::INVALID_FORMAT,
            self::DUPLICATE_ENTRY => 422,

            // Auth Error - 4xx
            self::UNAUTHORIZED, self::AUTHENTICATION_FAILED,
            self::TOKEN_EXPIRED, self::TOKEN_INVALID,
            self::SESSION_EXPIRED => 401,
            self::FORBIDDEN => 403,

            // Business Logic Error - 4xx
            self::USER_NOT_FOUND, self::CLIENT_NOT_FOUND,
            self::TRANSACTION_NOT_FOUND, self::MERCHANT_NOT_FOUND,
            self::INSUFFICIENT_BALANCE, self::INVALID_PAYMENT_METHOD,
            self::PAYMENT_FAILED, self::PAYMENT_EXPIRED, self::PAYMENT_PENDING,
            self::TRANSACTION_FAILED, self::TRANSACTION_EXPIRED,
            self::INVALID_AMOUNT, self::INVALID_CURRENCY,
            self::DUPLICATE_TRANSACTION, self::KYB_PENDING, self::KYB_REJECTED,
            self::CLIENT_SUSPENDED, self::MERCHANT_SUSPENDED,
            self::SETTLEMENT_FAILED, self::RECONCILIATION_FAILED => 400,

            // Not Found - 404
            self::NOT_FOUND, self::RESOURCE_NOT_FOUND,
            self::ENDPOINT_NOT_FOUND => 404,

            // Server Error - 5xx
            self::INTERNAL_SERVER_ERROR, self::DATABASE_ERROR,
            self::EXTERNAL_SERVICE_ERROR, self::PAYMENT_GATEWAY_ERROR,
            self::NETWORK_ERROR => 500,
            self::TOO_MANY_REQUESTS => 429,
            self::SERVICE_UNAVAILABLE => 503,
        };
    }
}
