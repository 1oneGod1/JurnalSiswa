#!/bin/sh
set -e

# Generate app key jika belum ada
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Jalankan migration
php artisan migrate --force

# Cache config & routes untuk production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Buat storage symlink
php artisan storage:link || true

mkdir -p /var/log/supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
