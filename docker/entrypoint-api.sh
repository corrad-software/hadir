#!/bin/sh
set -e

cd /var/www/html

wait_for_tcp() {
    host="$1"
    port="$2"
    label="$3"
    max="${4:-60}"
    i=0

    echo "Waiting for ${label} at ${host}:${port}..."
    while [ "$i" -lt "$max" ]; do
        if php -r "exit(@fsockopen(getenv('HOST'), (int) getenv('PORT')) ? 0 : 1);" 2>/dev/null; then
            echo "${label} is ready."
            return 0
        fi
        i=$((i + 1))
        sleep 1
    done

    echo "Timed out waiting for ${label}."
    return 1
}

if [ -n "$DB_HOST" ] && [ -n "$DB_PORT" ]; then
    HOST="$DB_HOST" PORT="$DB_PORT" wait_for_tcp "$DB_HOST" "$DB_PORT" "MySQL"
fi

if [ -n "$ATTENDANCE_DB_HOST" ] && [ -n "$ATTENDANCE_DB_PORT" ]; then
    HOST="$ATTENDANCE_DB_HOST" PORT="$ATTENDANCE_DB_PORT" wait_for_tcp "$ATTENDANCE_DB_HOST" "$ATTENDANCE_DB_PORT" "PostgreSQL"
fi

php artisan storage:link --force 2>/dev/null || true

if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi

if [ "$RUN_SEED" = "true" ]; then
    php artisan db:seed --force
fi

if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
