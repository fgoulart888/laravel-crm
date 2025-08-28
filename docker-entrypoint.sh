#!/usr/bin/env bash
set -e

# Gera APP_KEY se estiver vazio
php artisan key:generate --force || true

# Link de storage (idempotente)
php artisan storage:link || true

# Migrações e seeds
php artisan migrate --force
php artisan db:seed --force || true

exec "$@"
