# Payment Platform

Production-ready payment gateway authentication and API key management system.

## Features

- **Dual Authentication System**
  - Dashboard OAuth2 authentication (with refresh token)
  - API Key-based authentication (no refresh token)

- **API Key Management**
  - Create, update, revoke API keys
  - IP whitelist support
  - Configurable rate limiting
  - Secret regeneration

- **Security**
  - Role-based access control (Spatie Permissions)
  - Comprehensive audit trail
  - Rate limiting (Redis backend)
  - IP whitelist validation
  - Request signing support

- **Multi-language Support**
  - English & Indonesian
  - Accept-Language header detection

## Tech Stack

- **Framework**: Laravel 12
- **PHP**: 8.2+
- **Database**: MySQL 8.0+
- **Cache/Queue**: Redis
- **Authentication**: Laravel Passport (OAuth2)
- **Authorization**: Spatie Laravel Permission

## Requirements

- PHP >= 8.2
- Composer
- MySQL >= 8.0
- Redis >= 6.0
- Node.js & NPM (for frontend assets)

## Installation

```bash
# Clone repository
git clone <repository-url>
cd payment-platform

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
# DB_DATABASE=db_pg_lit
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Run migrations and seeders
php artisan migrate:fresh --seed

# Generate Passport clients
php scripts/generate-passport-secrets.php

# Build frontend assets
npm run build

# Start development server
php artisan serve
```

## Environment Configuration

### Database Setup

Update `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_pg_lit
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Redis Setup

Update `.env` for Redis:

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Passport OAuth2 Clients

Generate Passport secrets:

```bash
php scripts/generate-passport-secrets.php
```

Then update `.env` with generated values:

```env
PASSPORT_DASHBOARD_CLIENT_ID=<generated-id>
PASSPORT_DASHBOARD_CLIENT_SECRET=<generated-secret>

PASSPORT_API_SERVER_CLIENT_ID=<generated-id>
PASSPORT_API_SERVER_CLIENT_SECRET=<generated-secret>
```

## Default Test Accounts

| Email | Password | Role |
|-------|----------|------|
| superadmin@jdp.co.id | SuperAdmin123! | System Owner (Super Admin) |
| system-owner@jdp.co.id | password123 | System Owner Admin |
| client@jdp.co.id | password123 | Client Admin |
| hq-jakarta@jdp.co.id | password123 | Head Quarter Admin |
| merchant-001@jdp.co.id | password123 | Merchant Admin |

## Available Commands

```bash
# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Generate Passport secrets
php scripts/generate-passport-secrets.php

# Test API client
php scripts/test-api-client.php
```

## Security Notes

- **Production**: Set `APP_DEBUG=false` in `.env`
- **HTTPS**: Enable SSL/TLS in production
- **Passport Secrets**: Regenerate and keep secure in production
- **API Keys**: Rotate periodically
- **Rate Limiting**: Enabled by default, configure per API key
- **IP Whitelist**: Enable for production API keys

## Configuration

### Rate Limiting

Configure in `.env`:

```env
RATE_LIMIT_ENABLED=true
RATE_LIMIT_DEFAULT_MAX_ATTEMPTS=60
RATE_LIMIT_DEFAULT_DECAY_MINUTES=1
```

### API Security

```env
API_KEY_IP_WHITELIST_ENABLED=true
API_SIGNATURE_ENABLED=true
API_SIGNATURE_TOLERANCE_SECONDS=300
```

### Token Expiration

```env
PASSPORT_ACCESS_TOKEN_TTL=60        # minutes
PASSPORT_REFRESH_TOKEN_TTL=30       # days
PASSPORT_PAT_TOKEN_TTL=90           # days
```

## Documentation

- See `/docs` directory for API documentation
- Postman collection available in `/postman` directory

## Support

For issues and questions, contact the development team.

## License

Proprietary - All rights reserved.
