#!/bin/sh
set -eu

mkdir -p database
touch database/database.sqlite
# Apache runs as www-data; make the SQLite file and containing directory
# writable at runtime as well as during image build.
chown -R www-data:www-data database
chmod 775 database
chmod 664 database/database.sqlite

# Use the bundled SQLite store for the Render demo. This keeps startup independent
# of an external database credential while preserving the Laravel data layer.
export DB_CONNECTION=sqlite
export DB_DATABASE="$(pwd)/database/database.sqlite"
unset DB_URL

php artisan config:clear
php artisan migrate --force
php artisan db:seed --force
if [ "${DEMO_DATA_ENABLED:-false}" = "true" ]; then
    php artisan demo:populate --no-interaction
fi
php artisan storage:link --force

exec apache2-foreground
