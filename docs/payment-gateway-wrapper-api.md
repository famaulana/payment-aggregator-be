# Payment Gateway Wrapper API

**Version:** 1.0
**Base URL:** `{{base_url}}/api/v1`
**Content-Type:** `application/json`
**Accept:** `application/json`

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [Request Signing (HMAC-SHA256)](#request-signing-hmac-sha256)
4. [Standard Response Format](#standard-response-format)
5. [Response Codes](#response-codes)
6. [Rate Limiting](#rate-limiting)
7. [Endpoints](#endpoints)
   - [Authentication](#auth-endpoints)
   - [Payments](#payments)
   - [Payment Methods](#payment-methods)
   - [Balance](#balance)
   - [Webhooks](#webhooks)
8. [Payment Methods and Channels](#payment-methods-and-channels)
9. [Fee Structure](#fee-structure)
10. [Outbound Webhook Events](#outbound-webhook-events)
11. [Inbound Webhook from PG](#inbound-webhook-from-pg)
12. [Transaction Status Lifecycle](#transaction-status-lifecycle)
13. [Error Handling](#error-handling)

---

## Overview

The Payment Gateway Wrapper API is an abstraction layer that hides the complexity of integrating with multiple Payment Gateways (PG) from the client. The client communicates with a single consistent API, regardless of which PG processes the transaction behind the scenes.

**Architecture layers:**

```
Client Application
    |
    v
Auth Layer (API Key + IP Whitelist + Timestamp + HMAC-SHA256 + Rate Limit)
    |
    v
Core API (Payment, Balance, Webhook Controllers)
    |
    v
Router (PaymentRouterService -> selects appropriate gateway + calculates MDR)
    |
    v
Gateway Layer (Bayarind, CRING, ...)
    |
    v
Persistence (MySQL + Audit Logs + Webhook Logs + Gateway Logs)
```

**Entity hierarchy in this system:**

```
System Owner
    |
    v
Client (mitra / partner of JDP — owner of the API key)
    |
    v
Head Quarter (regional office of the client, optional)
    |
    v
Merchant (outlet / agent / branch of the client)
```

When a client calls the Payment API, `merchant_code` identifies which outlet or agent is processing the transaction. If omitted, the first active outlet of the client is used.

---

## Authentication

All payment API endpoints require the following HTTP headers on every request:

| Header | Type | Required | Description |
|--------|------|----------|-------------|
| `X-API-Key` | string | Yes | Your API key (`api_key` value) |
| `X-Timestamp` | integer | Yes | Unix timestamp (seconds) of the request. Must be within ±300 seconds of server time. |
| `X-Signature` | string | Yes | HMAC-SHA256 signature — see section below |
| `Content-Type` | string | Yes | Must be `application/json` |
| `Accept` | string | Yes | Must be `application/json` |
| `Accept-Language` | string | No | `en` or `id`. Defaults to `en`. |

The authentication middleware validates these in order:

1. **API Key lookup** — find the key in the database, confirm status is `active`
2. **IP Whitelist** — check request IP against the key's allowed IPs
3. **Timestamp validation** — reject requests older than 300 seconds (anti-replay)
4. **HMAC-SHA256 signature** — verify the request has not been tampered with
5. **Rate limit** — enforce per-minute and per-hour limits

---

## Request Signing (HMAC-SHA256)

Every request to a protected endpoint must include a valid `X-Signature` header computed as follows.

### Algorithm

```
data_to_sign = METHOD + "\n" + CANONICAL_URL + "\n" + TIMESTAMP + "\n" + SORTED_PAYLOAD

X-Signature = HMAC-SHA256(data_to_sign, api_secret)  →  lowercase hex string
```

### Components

| Component | Description | Example |
|-----------|-------------|---------|
| `METHOD` | Uppercase HTTP method | `POST` |
| `CANONICAL_URL` | Absolute path only (no host, no query string) | `/api/v1/payments` |
| `TIMESTAMP` | Same value as `X-Timestamp` header (unix integer as string) | `1708248000` |
| `SORTED_PAYLOAD` | See below |  |

### SORTED_PAYLOAD rules

**For GET / HEAD requests:**
Take all query parameters, remove `X-Signature` and `X-Timestamp` if present, sort keys alphabetically, then URL-encode:

```
from=2024-01-01&page=1&status=paid
```

**For POST / PUT / PATCH requests:**
Take the raw JSON body as a PHP array, remove `X-Signature` and `X-Timestamp` keys if present, sort keys alphabetically (ksort), then re-encode as JSON with `JSON_UNESCAPED_SLASHES`:

```json
{"amount":150000,"currency":"IDR","merchant_ref":"ORDER-001"}
```

### Implementation examples

**PHP:**

```php
$method    = 'POST';
$url       = '/api/v1/payments';
$timestamp = (string) time();
$body      = ['merchant_ref' => 'ORDER-001', 'amount' => 150000, 'currency' => 'IDR'];

ksort($body);
$sortedPayload = json_encode($body, JSON_UNESCAPED_SLASHES);

$dataToSign = $method . "\n" . $url . "\n" . $timestamp . "\n" . $sortedPayload;
$signature  = hash_hmac('sha256', $dataToSign, $apiSecret);
```

**Node.js:**

```javascript
const crypto = require('crypto');

const method    = 'POST';
const url       = '/api/v1/payments';
const timestamp = Math.floor(Date.now() / 1000).toString();
const body      = { merchant_ref: 'ORDER-001', amount: 150000, currency: 'IDR' };

const sorted       = Object.fromEntries(Object.entries(body).sort());
const sortedPayload = JSON.stringify(sorted);

const dataToSign = `${method}\n${url}\n${timestamp}\n${sortedPayload}`;
const signature  = crypto.createHmac('sha256', apiSecret).update(dataToSign).digest('hex');
```

**Python:**

```python
import hmac, hashlib, json, time

method    = 'POST'
url       = '/api/v1/payments'
timestamp = str(int(time.time()))
body      = {'merchant_ref': 'ORDER-001', 'amount': 150000, 'currency': 'IDR'}

sorted_payload = json.dumps(dict(sorted(body.items())), separators=(',', ':'), ensure_ascii=False)
data_to_sign   = f"{method}\n{url}\n{timestamp}\n{sorted_payload}"
signature      = hmac.new(api_secret.encode(), data_to_sign.encode(), hashlib.sha256).hexdigest()
```

### Timestamp tolerance

The server rejects requests where the `X-Timestamp` differs from the server clock by more than **300 seconds (5 minutes)**. Always generate the timestamp at the moment of sending, not at request build time.

---

## Standard Response Format

### Success

```json
{
  "response_code": "0000",
  "response_message": "Success",
  "data": { }
}
```

### Success with pagination

```json
{
  "response_code": "0000",
  "response_message": "Success",
  "data": [ ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8,
    "from": 1,
    "to": 20,
    "has_more_pages": true
  }
}
```

### Error

```json
{
  "response_code": "1000",
  "response_message": "Validation Error",
  "errors": {
    "amount": ["The amount field is required."]
  }
}
```

---

## Response Codes

| Code | HTTP | Meaning |
|------|------|---------|
| `0000` | 200 | Success |
| `0001` | 201 | Created |
| `0004` | 200 | Updated |
| `0005` | 200 | Deleted |
| `1000` | 422 | Validation Error |
| `2000` | 401 | Unauthorized |
| `2004` | 403 | Forbidden |
| `2006` | 401 | Invalid API Key |
| `2008` | 401 | API Key Revoked |
| `2010` | 401 | IP Not Allowed |
| `2011` | 401 | Invalid HMAC Signature |
| `2012` | 401 | Request Expired (timestamp out of range) |
| `2014` | 401 | API Key Required |
| `3001` | 400 | Client Not Found |
| `3002` | 400 | Transaction Not Found |
| `3003` | 400 | Merchant (Outlet) Not Found |
| `3005` | 400 | Invalid Payment Method |
| `3006` | 400 | Payment Failed |
| `4000` | 404 | Not Found |
| `5000` | 500 | Internal Server Error |
| `5003` | 500 | Payment Gateway Error |
| `5005` | 429 | Rate Limit Exceeded |

---

## Rate Limiting

Rate limits are set per API key and enforced via Redis:

| Limit | Default | Header when exceeded |
|-------|---------|----------------------|
| Per minute | 60 requests | Returns `5005` with HTTP 429 |
| Per hour | 1000 requests | Returns `5005` with HTTP 429 |

Limits are configured per API key. Contact the system owner to adjust limits for your integration.

---

## Endpoints

### Auth Endpoints

#### POST /api/v1/login

Authenticate with your API key and secret to receive an access token. This token is required for dashboard operations but NOT for the payment wrapper endpoints, which use API key + HMAC directly.

**Headers (no signature required for login):**

| Header | Value |
|--------|-------|
| `Content-Type` | `application/json` |
| `Accept` | `application/json` |

**Request body:**

```json
{
  "api_key": "pk_prod_jdp_1234567890abcdef",
  "api_secret": "sk_prod_jdp_secretkey1234567890abcdef"
}
```

**Response 200:**

```json
{
  "response_code": "0000",
  "response_message": "Login successful",
  "data": {
    "client": {
      "id": 1,
      "name": "PT Jago Digital Payment",
      "code": "JDP001"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "expires_at": "2024-02-18 16:00:00"
  }
}
```

---

### Payments

#### POST /api/v1/payments

Create a new payment transaction. All payment methods (QRIS, Virtual Account, E-Wallet, Electronic Money, PayLater) use this single endpoint. Differentiate by `payment_method` and `payment_channel`.

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `merchant_ref` | string | Yes | Your internal order/reference ID. Max 255 chars. |
| `merchant_code` | string | No | Outlet/agent code of your client account. If omitted, defaults to your first active outlet. |
| `payment_method` | string | Yes | See [Payment Methods and Channels](#payment-methods-and-channels) |
| `payment_channel` | string | Conditional | Required for methods with multiple channels (e.g., `BCA` for virtual account). |
| `amount` | integer | Yes | Amount in IDR (whole number, no decimals). Minimum 1. |
| `currency` | string | No | `IDR` only. Defaults to `IDR`. |
| `expired_at` | string | No | ISO 8601 datetime. Defaults to 24 hours from now. Must be in the future. |
| `customer.name` | string | Yes | Customer full name. Max 255 chars. |
| `customer.email` | string | Yes | Customer email address. |
| `customer.phone` | string | No | Customer phone number. Max 20 chars. |
| `items` | array | No | Line items. Recommended for reconciliation. |
| `items[].name` | string | Yes (if items) | Item name. |
| `items[].qty` | integer | Yes (if items) | Quantity. Min 1. |
| `items[].price` | integer | Yes (if items) | Unit price in IDR. Min 0. |
| `metadata` | object | No | Arbitrary key-value pairs. Stored and returned as-is. |
| `callback_url` | string | No | Override the default webhook callback URL for this transaction. |
| `redirect_url` | string | No | Override the default redirect URL for redirect-based payment methods. |

**Request example:**

```json
{
  "merchant_ref": "ORDER-20240218-001",
  "merchant_code": "MCH-JKT-001",
  "payment_method": "virtual_account",
  "payment_channel": "BCA",
  "amount": 150000,
  "currency": "IDR",
  "expired_at": "2024-02-19T15:00:00+07:00",
  "customer": {
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "phone": "081234567890"
  },
  "items": [
    { "name": "Tiket Konser", "qty": 2, "price": 75000 }
  ],
  "metadata": {
    "internal_order_id": "ORD-123",
    "notes": "VIP section"
  },
  "callback_url": "https://your-app.com/webhook/payment",
  "redirect_url": "https://your-app.com/order/complete"
}
```

**Response 201:**

```json
{
  "response_code": "0001",
  "response_message": "Payment Created",
  "data": {
    "transaction_id": "TXN-20240218-XYZABC",
    "merchant_ref": "ORDER-20240218-001",
    "outlet": {
      "code": "MCH-JKT-001",
      "name": "Outlet Jakarta Pusat"
    },
    "payment_method": "virtual_account",
    "payment_channel": "BCA Virtual Account",
    "status": "pending",
    "amount": 150000,
    "currency": "IDR",
    "fee": {
      "mdr_amount": 4500,
      "net_amount": 145500
    },
    "customer": {
      "name": "Budi Santoso",
      "email": "budi@example.com",
      "phone": "081234567890"
    },
    "payment_instruction": {
      "va_number": "1234000000000001",
      "bank": "BCA Virtual Account",
      "account_name": "Budi Santoso"
    },
    "expired_at": "2024-02-19T15:00:00+07:00",
    "created_at": "2024-02-18T15:00:00+07:00"
  }
}
```

**`payment_instruction` fields by method:**

| `payment_method` | Fields |
|------------------|--------|
| `virtual_account` | `va_number`, `bank`, `account_name` |
| `qris` | `qr_string`, `qr_url`, `qr_image_url` |
| `e_wallet` | `checkout_url`, `deeplink_url`, `wallet` |
| `electronic_money` | `checkout_url`, `card_type` |
| `paylater` | `checkout_url`, `provider` |
| `credit_card` | `checkout_url` |

---

#### GET /api/v1/payments

List payment transactions for your client account, with optional filters.

**Query parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | integer | Page number. Default: 1. |
| `per_page` | integer | Items per page. Default: 20. Max: 100. |
| `status` | string | Filter by status: `pending`, `paid`, `failed`, `expired`, `refunded`, `in_settlement`, `settled` |
| `payment_method` | string | Filter by method type: `qris`, `virtual_account`, `e_wallet`, etc. |
| `merchant_code` | string | Filter by specific outlet/agent code. |
| `from` | date | Filter `created_at >= YYYY-MM-DD` |
| `to` | date | Filter `created_at <= YYYY-MM-DD` |
| `search` | string | Search by `transaction_id` or `merchant_ref` |

**Response 200:**

```json
{
  "response_code": "0000",
  "response_message": "Success",
  "data": [
    {
      "transaction_id": "TXN-20240218-XYZABC",
      "merchant_ref": "ORDER-20240218-001",
      "payment_method": "virtual_account",
      "status": "paid",
      "amount": 150000,
      "currency": "IDR",
      "fee": { "mdr_amount": 4500, "net_amount": 145500 },
      "created_at": "2024-02-18T15:00:00+07:00"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 5,
    "last_page": 1,
    "from": 1,
    "to": 5,
    "has_more_pages": false
  }
}
```

---

#### GET /api/v1/payments/{transaction_id}

Get full detail of a single transaction.

**Path parameter:** `transaction_id` — the `TXN-*` value returned on creation.

**Response 200:**

```json
{
  "response_code": "0000",
  "response_message": "Success",
  "data": {
    "transaction_id": "TXN-20240218-XYZABC",
    "merchant_ref": "ORDER-20240218-001",
    "outlet": {
      "code": "MCH-JKT-001",
      "name": "Outlet Jakarta Pusat"
    },
    "payment_method": "virtual_account",
    "payment_channel": "BCA Virtual Account",
    "status": "paid",
    "amount": 150000,
    "currency": "IDR",
    "fee": { "mdr_amount": 4500, "net_amount": 145500 },
    "customer": {
      "name": "Budi Santoso",
      "email": "budi@example.com",
      "phone": "081234567890"
    },
    "payment_instruction": {
      "va_number": "1234000000000001",
      "bank": "BCA Virtual Account",
      "account_name": "Budi Santoso"
    },
    "paid_at": "2024-02-18T15:30:00+07:00",
    "settlement_status": "pending",
    "settlement_date": null,
    "expired_at": "2024-02-19T15:00:00+07:00",
    "created_at": "2024-02-18T15:00:00+07:00",
    "metadata": {
      "internal_order_id": "ORD-123"
    }
  }
}
```

---

#### POST /api/v1/payments/{transaction_id}/cancel

Cancel a `pending` payment. Only works on transactions that have not yet been paid.

**Request body:** none required.

**Response 200:**

```json
{
  "response_code": "0000",
  "response_message": "Payment cancelled successfully.",
  "data": {
    "transaction_id": "TXN-20240218-XYZABC",
    "status": "cancelled"
  }
}
```

---

#### POST /api/v1/payments/{transaction_id}/refund

Request a refund for a `paid` transaction. Partial refunds are supported.

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `amount` | integer | No | Refund amount in IDR. If omitted, refunds the full remaining amount. |
| `reason` | string | Yes | Reason for the refund. Max 500 chars. |
| `ref_id` | string | No | Your idempotency key. Safe to retry with the same `ref_id`. |

**Request example:**

```json
{
  "amount": 75000,
  "reason": "Customer requested partial refund — item out of stock",
  "ref_id": "REFUND-20240218-001"
}
```

**Response 200:**

```json
{
  "response_code": "0000",
  "response_message": "Refund processed",
  "data": {
    "refund_id": "RFN-20240218-ABCXYZ",
    "transaction_id": "TXN-20240218-XYZABC",
    "refund_amount": 75000,
    "status": "pending",
    "reason": "Customer requested partial refund — item out of stock",
    "created_at": "2024-02-18T16:00:00+07:00"
  }
}
```

---

### Payment Methods

#### GET /api/v1/payment-methods

List all active payment methods and channels available for your account, including fee information.

**Response 200:**

```json
{
  "response_code": "0000",
  "response_message": "Success",
  "data": [
    {
      "method_code": "virtual_account",
      "method_name": "Virtual Account",
      "channels": [
        {
          "channel_code": "va_bca",
          "channel_name": "BCA Virtual Account",
          "fee": { "type": "fixed", "amount": 4500 },
          "min_amount": 10000,
          "max_amount": 50000000
        },
        {
          "channel_code": "va_mandiri",
          "channel_name": "Mandiri Virtual Account",
          "fee": { "type": "fixed", "amount": 4500 },
          "min_amount": 10000,
          "max_amount": 50000000
        }
      ]
    },
    {
      "method_code": "qris",
      "method_name": "QRIS",
      "channels": [
        {
          "channel_code": "qris",
          "channel_name": "QRIS",
          "fee": { "type": "percentage", "percentage": 0.70 },
          "min_amount": 1000,
          "max_amount": 10000000
        }
      ]
    }
  ]
}
```

---

### Balance

#### GET /api/v1/balance

Get current balance of your client account.

**Response 200:**

```json
{
  "response_code": "0000",
  "response_message": "Success",
  "data": {
    "client_id": 1,
    "client_name": "PT Jago Digital Payment",
    "available_balance": 12500000.00,
    "pending_balance": 3000000.00,
    "minus_balance": 0.00,
    "currency": "IDR",
    "updated_at": "2024-02-18T15:30:00+07:00"
  }
}
```

---

#### GET /api/v1/balance/history

Get paginated balance mutation history.

**Query parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | integer | Page number. Default: 1. |
| `per_page` | integer | Default: 20. Max: 100. |
| `type` | string | Filter by transaction type. |
| `from` | date | Filter `created_at >= YYYY-MM-DD` |
| `to` | date | Filter `created_at <= YYYY-MM-DD` |

---

### Webhooks

#### POST /api/v1/webhooks/test

Send a test webhook payload to verify your callback endpoint is reachable and working.

**Request body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `callback_url` | string | No | Override URL to test. If omitted, uses the default configured URL. |

**Request example:**

```json
{
  "callback_url": "https://your-app.com/webhook/payment"
}
```

**Response 200:**

```json
{
  "response_code": "0000",
  "response_message": "Success",
  "data": {
    "message": "Test webhook dispatched",
    "callback_url": "https://your-app.com/webhook/payment",
    "payload": {
      "event": "payment.paid",
      "event_id": "EVT-TEST-ABCDEF",
      "created_at": "2024-02-18T15:00:00+07:00",
      "data": {
        "transaction_id": "TXN-TEST-ABCDEF",
        "merchant_ref": "TEST-ORDER-001",
        "status": "paid",
        "amount": 150000,
        "paid_at": "2024-02-18T15:00:00+07:00"
      },
      "signature": "test-signature"
    }
  }
}
```

---

#### POST /api/v1/webhooks/inbound/{gateway}

This endpoint is called by the Payment Gateway itself when a transaction status changes. It does **not** require client authentication headers. Signature verification is performed internally using the PG's webhook secret.

| Path param | Description |
|------------|-------------|
| `gateway` | PG code: `bayarind`, `cring` |

The system returns HTTP 200 immediately and processes the webhook asynchronously via queue.

---

## Payment Methods and Channels

Use the `payment_method` and `payment_channel` combination in `POST /v1/payments`:

| `payment_method` | `payment_channel` | Provider | Notes |
|------------------|-------------------|----------|-------|
| `virtual_account` | `BCA` | Bayarind | BCA Virtual Account |
| `virtual_account` | `MANDIRI` | Bayarind | Mandiri Virtual Account |
| `virtual_account` | `BRI` | Bayarind | BRI Virtual Account |
| `virtual_account` | `BNI` | Bayarind | BNI Virtual Account |
| `qris` | *(omit)* | Bayarind | QRIS All Banks |
| `e_wallet` | `DANA` | Bayarind | DANA |
| `e_wallet` | `OVO` | Bayarind | OVO |
| `e_wallet` | `SHOPEEPAY` | Bayarind | ShopeePay |
| `electronic_money` | `MANDIRI` | CRING | E-Money Mandiri (NFC card) |
| `electronic_money` | `BCA` | CRING | Flazz BCA (NFC card) |
| `electronic_money` | `BRI` | CRING | Brizzi BRI (NFC card) |
| `electronic_money` | `BNI` | CRING | TapCash BNI (NFC card) |
| `paylater` | `AKULAKU` | Bayarind | AkuLaku PayLater |
| `paylater` | `KREDIVO` | Bayarind | Kredivo PayLater |

---

## Fee Structure

Fees are deducted from the transaction amount. The `fee.mdr_amount` in the response is the total fee charged. The `fee.net_amount` is what your account receives.

```
net_amount = amount - mdr_amount
```

| Payment Method | Channel | Fee Type | Fee Charged to Client |
|----------------|---------|----------|-----------------------|
| Virtual Account | BCA | Fixed | Rp 4,500 |
| Virtual Account | Mandiri | Fixed | Rp 4,500 |
| Virtual Account | BRI | Fixed | Rp 4,500 |
| Virtual Account | BNI | Fixed | Rp 4,500 |
| QRIS | All Banks | Percentage | 0.70% |
| E-Wallet | DANA | Percentage | 2.50% |
| E-Wallet | OVO | Percentage | 2.50% |
| E-Wallet | ShopeePay | Percentage | 2.50% |
| Electronic Money | E-Money Mandiri | Percentage | 2.00% |
| Electronic Money | Flazz BCA | Percentage | 2.00% |
| Electronic Money | Brizzi BRI | Percentage | 2.00% |
| Electronic Money | TapCash BNI | Percentage | 2.00% |
| PayLater | AkuLaku | Percentage | 3.00% |
| PayLater | Kredivo | Percentage | 3.00% |

**Example calculation (QRIS, amount Rp 100,000):**

```
mdr_amount = 100,000 × 0.70% = 700
net_amount = 100,000 - 700   = 99,300
```

**Example calculation (BCA VA, amount Rp 200,000):**

```
mdr_amount = 4,500 (fixed)
net_amount = 200,000 - 4,500 = 195,500
```

---

## Outbound Webhook Events

The system sends a POST request to your `callback_url` when a transaction status changes.

### Webhook payload structure

```json
{
  "event": "payment.paid",
  "event_id": "EVT-20240218-ABCXYZ",
  "created_at": "2024-02-18T15:30:00+07:00",
  "data": {
    "transaction_id": "TXN-20240218-XYZABC",
    "merchant_ref": "ORDER-20240218-001",
    "status": "paid",
    "amount": 150000,
    "paid_at": "2024-02-18T15:30:00+07:00"
  },
  "signature": "a1b2c3d4e5f6..."
}
```

### Verifying the webhook signature

```
signature = HMAC-SHA256(JSON.stringify(data), webhook_secret)
```

Where `webhook_secret` is the value configured in your client's `settlement_config.webhook_secret`.

### Event types

| Event | Trigger |
|-------|---------|
| `payment.pending` | Payment created successfully |
| `payment.paid` | Payment confirmed by PG |
| `payment.failed` | Payment failed or denied by PG |
| `payment.expired` | Payment not completed before `expired_at` |
| `payment.refunded` | Full refund completed |
| `settlement.processed` | Settlement batch processed |

### Retry policy

Outbound webhooks are delivered via queue with automatic retry:

| Attempt | Delay |
|---------|-------|
| 1 | Immediate |
| 2 | 10 seconds |
| 3 | 30 seconds |
| 4 | 60 seconds |
| 5 | 120 seconds |

If all 5 attempts fail, the webhook is logged as failed and no further retries occur. Check `POST /v1/webhooks/test` to verify your endpoint is reachable.

### Your endpoint requirements

- Must return HTTP 2xx within 15 seconds
- Must accept `Content-Type: application/json`
- Must be publicly accessible (not behind VPN or localhost)

---

## Inbound Webhook from PG

The gateway sends callbacks to our system at:

```
POST /api/v1/webhooks/inbound/{gateway}
```

These are handled internally. You do not need to configure anything on your end for this.

---

## Transaction Status Lifecycle

```
PENDING
  |-- (PG confirms payment)  --> PAID --> IN_SETTLEMENT --> SETTLED
  |-- (PG denies / error)    --> FAILED
  |-- (expired_at reached)   --> EXPIRED
  |-- (cancel requested)     --> FAILED

PAID
  |-- (refund requested, partial) --> PAID (refunded_amount increases)
  |-- (refund requested, full)    --> REFUNDED
```

**Terminal states** (no further transitions possible): `FAILED`, `EXPIRED`, `REFUNDED`, `SETTLED`

---

## Error Handling

### Validation error (422)

```json
{
  "response_code": "1000",
  "response_message": "Validation Error",
  "errors": {
    "amount": ["The amount field is required."],
    "customer.email": ["The customer.email must be a valid email address."]
  }
}
```

### Authentication error (401)

```json
{
  "response_code": "2011",
  "response_message": "Invalid HMAC Signature"
}
```

### Business logic error (400)

```json
{
  "response_code": "3002",
  "response_message": "Transaction not found"
}
```

### Server error (500)

```json
{
  "response_code": "5003",
  "response_message": "Payment gateway error"
}
```

### Rate limit exceeded (429)

```json
{
  "response_code": "5005",
  "response_message": "Too many requests"
}
```
