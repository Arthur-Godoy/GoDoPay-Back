#!/bin/sh
set -e

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
