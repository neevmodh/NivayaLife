#!/bin/sh
set -e

export PORT="${PORT:-8080}"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/sites-enabled/default

export QUEUE_WORKERS="${QUEUE_WORKERS:-1}"
envsubst '${QUEUE_WORKERS}' < /etc/supervisor/conf.d/supervisord.conf.template > /etc/supervisor/conf.d/supervisord.conf

mkdir -p /run/php

cd /var/www/html

# Managed MySQL providers (e.g. Aiven) require SSL and hand you a CA cert
# rather than a file path — paste its PEM contents into DB_SSL_CA_CONTENT
# and we materialize it here for Laravel's MYSQL_ATTR_SSL_CA option.
if [ -n "$DB_SSL_CA_CONTENT" ]; then
    mkdir -p /etc/ssl/certs/app
    printf '%s' "$DB_SSL_CA_CONTENT" > /etc/ssl/certs/app/db-ca.pem
    export MYSQL_ATTR_SSL_CA=/etc/ssl/certs/app/db-ca.pem
fi

# Wait for the database — managed MySQL can take a few seconds to accept
# connections right after (re)provisioning or waking from idle.
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database at $DB_HOST:${DB_PORT:-3306}..."
    for i in $(seq 1 30); do
        php -r '
            $opts = [];
            if ($ca = getenv("MYSQL_ATTR_SSL_CA")) { $opts[PDO::MYSQL_ATTR_SSL_CA] = $ca; }
            new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306), getenv("DB_USERNAME"), getenv("DB_PASSWORD"), $opts);
        ' 2>/dev/null && break
        sleep 2
    done
fi

php artisan migrate --force
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
