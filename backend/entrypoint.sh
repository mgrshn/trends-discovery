#!/bin/sh
set -e

# Install dependencies if vendor is missing (host volume shadows image build)
if [ ! -f "vendor/autoload.php" ]; then
    echo "[entrypoint] vendor/ missing — running composer install..."
    composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --prefer-dist
fi

# Run migrations
php artisan migrate --force --no-interaction

# Publish Filament assets
php artisan filament:assets --no-interaction 2>/dev/null || true

# Seed categories if table is empty
php artisan db:seed --class=CategorySeeder --force --no-interaction 2>/dev/null || true

php artisan config:cache --no-interaction 2>/dev/null || true
php artisan route:cache  --no-interaction 2>/dev/null || true

exec "$@"
