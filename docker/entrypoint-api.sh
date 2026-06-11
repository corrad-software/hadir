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

if [ -n "$MYSQL_ROOT_PASSWORD" ]; then
    php /var/www/html/docker/mysql/bootstrap-grants.php
fi

if [ "$RUN_MIGRATIONS" = "true" ]; then
    if ! php artisan migrate --force; then
        echo ""
        echo "Migration failed. If you see 'Access denied' for database kedatangan/sso_hadir:"
        echo "  1. Add MYSQL_ROOT_PASSWORD (Coolify MySQL root password) to env and redeploy, OR"
        echo "  2. Run docker/mysql/grant-schemas.sql as MySQL root (Workbench / Coolify terminal)"
        echo ""
        exit 1
    fi
fi

if [ "$RUN_SEED" = "true" ]; then
    php artisan db:seed --force
fi

if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    if [ -d resources/views ]; then
        php artisan view:cache
    fi
fi

exec "$@"
