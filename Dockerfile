# --- Stage 1: Build Assets ---
FROM dunglas/frankenphp:1.3-php8.4-alpine AS builder

# Install system dependencies + Node.js for the build
RUN apk add --no-cache \
    libpq-dev libpng-dev libzip-dev zlib-dev freetype-dev libjpeg-turbo-dev \
    nodejs npm $PHPIZE_DEPS

WORKDIR /var/www/html
COPY . .

# Install PHP dependencies (needed for Wayfinder build step)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev

# Install NPM dependencies and Build
RUN npm install && npm run build

# --- Stage 2: Production Image ---
FROM dunglas/frankenphp:1.3-php8.4-alpine

RUN apk add --no-cache \
    libpq-dev libpng-dev libzip-dev zlib-dev freetype-dev libjpeg-turbo-dev

# Install extensions
RUN docker-php-ext-install pdo pdo_pgsql pcntl bcmath gd zip \
    && pecl install redis && docker-php-ext-enable redis

WORKDIR /var/www/html

# Copy from the builder stage
COPY --from=builder /var/www/html /var/www/html

# Final setup
RUN chown -R www-data:www-data storage bootstrap/cache
ENTRYPOINT ["php", "artisan", "octane:frankenphp", "--port=80", "--host=0.0.0.0"]
