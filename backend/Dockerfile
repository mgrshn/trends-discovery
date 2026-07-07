FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        zip \
        unzip \
        curl \
        git \
    && docker-php-ext-install pdo pdo_pgsql zip opcache pcntl intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install dependencies first (layer-cached unless composer files change)
COPY composer.json ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .

RUN composer dump-autoload --optimize --no-dev

# Generate app key if .env exists; skip gracefully otherwise
RUN cp .env.example .env 2>/dev/null || true

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
