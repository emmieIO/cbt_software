# --- Stage 1: Builder (PHP + Node + Extensions) ---
FROM dunglas/frankenphp:1.3-php8.4-alpine AS builder

# 1. Install System Dependencies
RUN apk add --no-cache \
    libpq-dev libpng-dev libzip-dev zlib-dev freetype-dev libjpeg-turbo-dev \
    nodejs npm autoconf dpkg-dev dpkg file g++ gcc libc-dev make pkgconf re2c

# 2. Install PHP Extensions (Required for Composer/OpenSpout/Wayfinder)
RUN docker-php-ext-install pdo pdo_pgsql pcntl bcmath gd zip

WORKDIR /var/www/html
COPY . .

# 3. Install PHP dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader

# 4. Install Node dependencies and Build Assets
RUN npm install && npm run build

# --- Stage 2: Production Image ---
FROM dunglas/frankenphp:1.3-php8.4-alpine

# Install only runtime system dependencies
RUN apk add --no-cache \
    libpq-dev libpng-dev libzip-dev zlib-dev freetype-dev libjpeg-turbo-dev

# Install extensions for the final image
RUN docker-php-ext-install pdo pdo_pgsql pcntl bcmath gd zip \
    && pecl install redis && docker-php-ext-enable redis

WORKDIR /var/www/html

# Copy the entire built application from the builder stage
COPY --from=builder /var/www/html /var/www/html

# Ensure permissions are correct for Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

ENTRYPOINT ["php", "artisan", "octane:frankenphp", "--port=80", "--host=0.0.0.0"]
