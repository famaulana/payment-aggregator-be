# Payment Platform - Complete Auth System

Production-ready authentication and API key management system for payment gateway operations.

## Features

- **Dual Authentication System**
  - FE Dashboard (User login with refresh token)
  - API Server (API Key based, no refresh token)

- **API Key Management**
  - Create, update, revoke API keys
  - IP whitelist support
  - Configurable rate limiting
  - Secret regeneration

- **Security Features**
  - Comprehensive audit trail
  - Rate limiting (Redis/File backend)
  - IP whitelist validation
  - Request signing support
  - Role-based access control

- **Multi-language Support**
  - English & Indonesian
  - Accept-Language header detection

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2
- **Database**: MySQL 8.0
- **Cache**: Redis (optional)
- **Authentication**: Laravel Passport (OAuth2)
- **Authorization**: Spatie Permissions

## Quick Start

```bash
# Clone repository
git clone https://github.com/your-org/payment-platform.git
cd payment-platform

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run setup
chmod +x setup.sh
./setup.sh

# Generate Passport secrets
php scripts/generate-passport-secrets.php
```

## Environment Variables

```env
# Application
APP_NAME="PaymentPlatform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.pg-lit.com

# Database
DB_DATABASE=payment_platform
DB_USERNAME=payment_user
DB_PASSWORD=your_secure_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password

# Passport (Generate using scripts/generate-passport-secrets.php)
PASSPORT_DASHBOARD_CLIENT_ID=
PASSPORT_DASHBOARD_CLIENT_SECRET=
PASSPORT_API_SERVER_CLIENT_ID=
PASSPORT_API_SERVER_CLIENT_SECRET=
```

## Test Users

| Email | Password | Role |
|-------|----------|------|
| system-owner@pg-lit.test | password123 | System Owner |
| client@pg-lit.test | password123 | Client |
| ho@pg-lit.test | password123 | Head Office |
| merchant@pg-lit.test | password123 | Merchant |

## API Endpoints

### Authentication (Public)
- `POST /api/v1/auth/login` - Dashboard login
- `POST /api/v1/auth/refresh` - Refresh access token
- `POST /api/v1/auth/api/login` - API Server login (API Key)

### Authentication (Protected)
- `POST /api/v1/auth/logout` - Logout current session
- `POST /api/v1/auth/logout-all` - Logout all sessions
- `GET /api/v1/auth/me` - Get current user profile
- `GET /api/v1/auth/tokens` - List active tokens

### API Key Management (System Owner only)
- `GET /api/v1/api-keys` - List API keys (paginated)
- `POST /api/v1/api-keys` - Create new API key
- `GET /api/v1/api-keys/{id}` - Get API key details
- `PUT /api/v1/api-keys/{id}` - Update API key
- `POST /api/v1/api-keys/{id}/revoke` - Revoke API key
- `POST /api/v1/api-keys/{id}/regenerate-secret` - Regenerate secret
- `POST /api/v1/api-keys/{id}/toggle-status` - Toggle active/inactive
- `GET /api/v1/api-keys/client/{id}` - Get client's API keys

### Audit Logs (System Owner only)
- `GET /api/v1/audit-logs` - List audit logs (paginated, filterable)
- `GET /api/v1/audit-logs/{id}` - Get audit log details

## Postman Collection

Complete Postman collection available in `/postman` directory:

- `Payment_Platform_API.postman_collection.json` - API endpoints
- `environments/Local.postman_environment.json` - Local environment
- `environments/Staging.postman_environment.json` - Staging environment
- `environments/Production.postman_environment.json` - Production environment

Import into Postman and configure environment variables.

## Documentation

- `/docs/API_DOCUMENTATION.md` - Complete API reference
- `/docs/DEPLOYMENT_GUIDE.md` - Production deployment guide

## Security

- All endpoints require HTTPS in production
- Bearer token authentication
- API Secret hashed using bcrypt
- IP whitelist support per API key
- Comprehensive audit logging
- Rate limiting per API key

## Token Configuration

| Client Type | Access Token | Refresh Token | Purpose |
|-------------|--------------|---------------|---------|
| Dashboard | 60 minutes | 30 days | FE Web/Mobile Apps |
| API Server | 60 minutes | None | Server-to-Server |

## Response Format

All API responses follow standard format:

```json
{
  "response_code": "0000",
  "response_message": "Success message",
  "data": { ... },
  "meta": { ... }
}
```

## Response Codes

| Code | Category |
|------|----------|
| 0000-0999 | Success |
| 1000-1999 | Validation errors |
| 2000-2999 | Authentication errors |
| 3000-3999 | Business logic errors |
| 4000-4999 | Not found errors |
| 5000-5999 | Server errors |

## Rate Limiting

Default limits per API key:
- 60 requests per minute
- 1000 requests per hour

Customizable per API key.

## Support

- Email: support@pg-lit.com
- Documentation: https://docs.pg-lit.com
- Status Page: https://status.pg-lit.com

## License

Proprietary. All rights reserved.
