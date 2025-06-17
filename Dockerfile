FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libsqlite3-dev \
    zip \
    git \
    unzip \
    curl \
    libpq-dev \
    supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_pgsql pdo_sqlite pgsql zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader

COPY .docker/supervisord/supervisord.conf /etc/supervisor/supervisord.conf
COPY .docker/supervisord/conf.d/ /etc/supervisor/conf.d/

COPY . .

RUN mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/testing \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/bootstrap/cache \
    && touch /var/www/html/storage/logs/laravel.log

RUN composer dump-autoload --optimize --no-scripts

RUN if [ ! -f .env ]; then cp .env.example .env; fi

RUN chmod +x artisan

RUN php artisan key:generate --ansi || echo "Key generation failed, continuing..."

RUN php artisan storage:link || echo "Storage link failed, continuing..."

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && chmod 664 /var/www/html/storage/logs/laravel.log

RUN echo "=== Verifying Permissions ===" \
    && ls -la /var/www/html/storage/ \
    && ls -la /var/www/html/bootstrap/ \
    && ls -la /var/www/html/storage/logs/ \
    && echo "=== Testing write permissions ===" \
    && su www-data -s /bin/bash -c "touch /var/www/html/storage/logs/test.log" \
    && su www-data -s /bin/bash -c "touch /var/www/html/bootstrap/cache/test.cache" \
    && echo "Permissions test passed"

USER www-data

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8000 || exit 1

USER root
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]