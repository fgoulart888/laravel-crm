#!/usr/bin/env bash
set -e
cd /var/www/html

# cria .env se não existir
[ -f .env ] || cp .env.example .env

# permissões essenciais
chown -R www-data:www-data storage bootstrap/cache .env || true
chmod -R 775 storage bootstrap/cache || true
chmod 664 .env || true

# prepara app
php artisan key:generate --force || true
php artisan storage:link || true
php artisan config:clear || true
php artisan route:clear || true
php artisan cache:clear || true

# migrations e seeds (idempotentes)
php artisan migrate --force || true
php artisan db:seed --force || true

exec "$@"
