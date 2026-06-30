#!/bin/sh
set -e

cd /var/www/html

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -f ".env" ]; then
    cp .env.example .env
    php artisan key:generate --force
fi

if [ ! -f "database/database.sqlite" ]; then
    touch database/database.sqlite
    chown www-data:www-data database/database.sqlite 2>/dev/null || true
fi

php artisan migrate --force --no-interaction || true

php artisan config:clear --no-interaction 2>/dev/null || true
php artisan storage:link --force --no-interaction 2>/dev/null || true

exec "$@"
