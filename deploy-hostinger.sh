#!/bin/bash
# ==============================================================================
# LostFinder - Hostinger Automated Deployment Script
# Usage on Hostinger SSH: bash deploy-hostinger.sh
# ==============================================================================

set -e

echo "=========================================================="
echo "🚀 Starting LostFinder Deployment on Hostinger..."
echo "=========================================================="

# 1. Pull latest code from GitHub
echo "[1/6] Pulling latest code from main branch..."
git pull origin main

# 2. Install/update Composer dependencies
echo "[2/6] Installing production PHP packages..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Database migrations & seeders
echo "[3/6] Running database migrations..."
php artisan migrate --force

# 4. Storage symlink
echo "[4/6] Linking storage folder..."
php artisan storage:link || true

# 5. Clear old caches and generate optimized production caches
echo "[5/6] Optimizing configuration, routes, and views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Set correct permissions
echo "[6/6] Ensuring storage permissions..."
chmod -R 775 storage bootstrap/cache

echo "=========================================================="
echo "✅ LostFinder Deployment Completed Successfully!"
echo "=========================================================="
