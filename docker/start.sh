#!/bin/sh
set -eu

mkdir -p database
touch database/database.sqlite

# Use the bundled SQLite store for the Render demo. This keeps startup independent
# of an external database credential while preserving the Laravel data layer.
export DB_CONNECTION=sqlite
export DB_DATABASE=/app/database/database.sqlite
unset DB_URL

php artisan migrate --force
php artisan db:seed --force
php artisan storage:link --force

exec apache2-foreground
