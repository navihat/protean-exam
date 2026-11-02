#!/bin/sh
# First-run bootstrap so `docker compose up` alone is enough (T02).
set -e
cd /var/www/html

[ -f .env ] || cp .env.example .env
[ -f vendor/autoload.php ] || composer install --no-interaction --prefer-dist --no-progress

grep -q '^APP_KEY=base64:' .env || php artisan key:generate --force

if [ "${AUTO_MIGRATE:-true}" = "true" ]; then
    php artisan migrate --force
    php artisan db:seed --force   # seeders are idempotent (CODING_CONTRACT C3 / ROADMAP PR3)
fi

exec "$@"
