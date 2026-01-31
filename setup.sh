# Setup Script for Payment Platform
# Run this script after fresh installation

#!/bin/bash

echo "================================"
echo "Payment Platform Setup Script"
echo "================================"
echo ""

# Check if .env exists
if [ ! -f .env ]; then
    echo "Copying .env.example to .env..."
    cp .env.example .env
fi

# Install dependencies
echo "Installing composer dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "Installing npm dependencies..."
npm install

# Generate application key
echo "Generating application key..."
php artisan key:generate

# Run migrations
echo "Running database migrations..."
php artisan migrate:fresh --seed

# Install Passport
echo "Installing Passport..."
php artisan passport:install

# Create symbolic links
echo "Creating symbolic links..."
php artisan storage:link

# Cache configuration
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
echo "Setting permissions..."
chmod -R 775 storage bootstrap/cache

echo ""
echo "================================"
echo "Setup completed successfully!"
echo "================================"
echo ""
echo "IMPORTANT: Update the following values in .env:"
echo "1. PASSPORT_DASHBOARD_CLIENT_SECRET"
echo "2. PASSPORT_API_SERVER_CLIENT_SECRET"
echo ""
echo "Test Users:"
echo "  - system-owner@pg-lit.test (Password: password123)"
echo "  - client@pg-lit.test (Password: password123)"
echo "  - ho@pg-lit.test (Password: password123)"
echo "  - merchant@pg-lit.test (Password: password123)"
echo ""
