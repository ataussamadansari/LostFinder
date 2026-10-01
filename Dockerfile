FROM php:8.2-cli-alpine

# Install system dependencies & PHP extensions
RUN apk add --no-cache \
    curl \
    git \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite \
    sqlite-dev \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_sqlite mbstring zip exif pcntl bcmath intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create SQLite database file if not exists
RUN mkdir -p database && touch database/database.sqlite

# Directory permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache database

# Entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
