# --- Stage 1: Builder ---
FROM dunglas/frankenphp:1.3-php8.4-alpine AS builder

# 1. Install Build Tools and dependencies
RUN apk add --no-cache \
    libpq-dev libpng-dev libzip-dev zlib-dev freetype-dev libjpeg-turbo-dev \
    nodejs npm autoconf dpkg-dev dpkg file g++ gcc libc-dev make pkgconf re2c

# 2. Install & Enable PHP Extensions
RUN docker-php-ext-install pdo pdo_pgsql pcntl bcmath gd zip \
    && pecl install redis \
    && docker-php-ext-enable redis

WORKDIR /var/www/html
COPY . .

# 3. Install PHP dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader

# 4. Build Frontend Assets
RUN npm install && npm run build

# --- Stage 2: Production ---
FROM dunglas/frankenphp:1.3-php8.4-alpine

# Install only the runtime libraries needed for the extensions to run
RUN apk add --no-cache \
    libpq libpng libzip zlib freetype libjpeg-turbo

# Copy the compiled extensions and PHP config from the builder
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

WORKDIR /var/www/html

# Copy the entire built application
COPY --from=builder /var/www/html /var/www/html

# Final setup
RUN chown -R www-data:www-data storage bootstrap/cache

ENTRYPOINT ["php", "artisan", "octane:frankenphp", "--port=80", "--host=0.0.0.0"]
