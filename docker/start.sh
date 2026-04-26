#!/bin/sh
set -e

# Laravel butuh .env exist walau pakai env vars dari Render
if [ ! -f /var/www/html/.env ]; then
    touch /var/www/html/.env
fi

generate_app_key() {
    php -r 'echo "base64:".base64_encode(random_bytes(32));'
}

valid_app_key() {
    php -r '
        $key = getenv("APP_KEY") ?: "";
        if (str_starts_with($key, "base64:")) {
            $decoded = base64_decode(substr($key, 7), true);
            exit(strlen($decoded) === 32 ? 0 : 1);
        }

        exit(strlen($key) === 32 ? 0 : 1);
    '
}

if ! valid_app_key; then
    export APP_KEY="$(generate_app_key)"
fi

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_PATH="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    mkdir -p "$(dirname "$DB_PATH")"
    touch "$DB_PATH"
    chown www-data:www-data "$(dirname "$DB_PATH")" "$DB_PATH" || true
fi

php artisan migrate --force

# Seed database hanya jika belum ada user (first deploy)
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -n 1 | tr -d '[:space:]')
if [ "$USER_COUNT" = "0" ]; then
    echo "Database kosong, jalankan seeder..."
    php artisan db:seed --force || true
fi

# Clear cache dulu untuk pastikan env vars yang baru terbaca
php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true

# Pastikan storage writable untuk session file
chmod -R 775 /var/www/html/storage
chown -R www-data:www-data /var/www/html/storage

mkdir -p /var/log/supervisor /var/run/php
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
