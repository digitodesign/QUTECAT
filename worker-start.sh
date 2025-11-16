#!/bin/bash
# Queue Worker Start Script for Railway
# This script is used by QUTECAT-WORKER service

cd backend/install

# Install dependencies if needed
if [ ! -d "vendor" ]; then
    composer install --optimize-autoloader --no-dev --no-interaction
fi

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Run queue worker
php artisan queue:work redis \
    --sleep=3 \
    --tries=3 \
    --timeout=60 \
    --memory=512 \
    --verbose
