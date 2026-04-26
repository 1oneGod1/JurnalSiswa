FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    curl \
    zip \
    unzip \
    git \
    libpng-dev \
    libsqlite3-dev \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite pcntl mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /var/log/supervisor /var/run/nginx

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer run-script post-autoload-dump || true

RUN mkdir -p storage/framework/sessions \
              storage/framework/views \
              storage/framework/cache/data \
              storage/logs \
              database \
              bootstrap/cache \
    && touch .env \
    && touch database/database.sqlite \
    && chmod -R 775 storage bootstrap/cache \
    && chmod -R 775 database \
    && chown -R www-data:www-data storage bootstrap/cache database

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
