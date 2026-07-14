#!/bin/sh
set -e

export PORT="${PORT:-8080}"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/sites-enabled/default

mkdir -p /run/php

cd /var/www/html

# Wait for the database — Railway's MySQL can take a few seconds to accept
# connections right after (re)provisioning.
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at $DB_HOST:${DB_PORT:-3306}..."
    for i in $(seq 1 30); do
        php -r "new PDO('mysql:host=$DB_HOST;port=${DB_PORT:-3306}', getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null && break
        sleep 2
    done
fi

php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
