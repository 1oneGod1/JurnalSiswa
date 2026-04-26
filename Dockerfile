FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    curl \
    zip \
    unzip \
    git \
    nodejs \
    npm \
    libpng-dev \
    libzip-dev \
    libonig-dev \
    && docker-php-ext-install pdo pdo_mysql pcntl mbstring zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN mkdir -p /var/log/supervisor /var/run/nginx

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN composer run-script post-autoload-dump || true
RUN npm run build && rm -rf node_modules

RUN mkdir -p storage/framework/sessions \
              storage/framework/views \
              storage/framework/cache/data \
              storage/logs \
              bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 8080

CMD ["/start.sh"]
