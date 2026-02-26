FROM dunglas/frankenphp:1.3-php8.3-alpine

# Install Postgres and PHP extensions
RUN apk add --no-cache libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pcntl bcmath gd zip \
    && apk add --no-cache $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis

# Set the internal container path
WORKDIR /var/www/html

# Copy your local code into the container's html folder
COPY . .

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --optimize-autoloader --no-dev

# Set permissions for Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

# Start FrankenPHP
ENTRYPOINT ["php", "artisan", "octane:frankenphp", "--port=80", "--host=0.0.0.0"]
