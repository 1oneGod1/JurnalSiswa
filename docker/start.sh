#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link || true

mkdir -p /var/log/supervisor /var/run/php
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
