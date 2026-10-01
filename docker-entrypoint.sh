#!/bin/sh
set -e

# Ensure storage directories exist
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache database
touch database/database.sqlite
chmod -R 775 storage bootstrap/cache database

# Create storage symlink
php artisan storage:link || true

# Ensure APP_KEY exists
if [ -z "$APP_KEY" ]; then
    echo "Generating application encryption key..."
    php artisan key:generate --force
fi

# Run database migrations and seed default data
php artisan migrate --force
php artisan db:seed --force

# Cache routes and views for production performance
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Start server using the environment PORT provided by Render/Railway (default: 8000)
PORT=${PORT:-8000}
echo "Starting LostFinder API server on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT
