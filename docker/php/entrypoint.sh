#!/usr/bin/env sh
set -e

# --- 1. Fix volume permissions on every container boot ---
mkdir -p /var/www/html/storage/framework/cache \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# --- 2. Wait for MySQL to be ready ---
if [ -n "${DB_HOST:-}" ]; then
    until php -r '
        try {
            new PDO(
                sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT") ?: "3306", getenv("DB_DATABASE")),
                getenv("DB_USERNAME"),
                getenv("DB_PASSWORD")
            );
        } catch (Throwable $e) {
            exit(1);
        }
    '; do
        echo "Waiting for database at ${DB_HOST}:${DB_PORT:-3306}..."
        sleep 2
    done
fi

php artisan storage:link --force >/dev/null 2>&1 || true

# --- 3. Run migrations ---
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
    php artisan db:seed
fi

# --- 4. Cache configurations ---
if [ "${CACHE_CONFIG:-true}" = "true" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
