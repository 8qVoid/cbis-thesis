#!/bin/sh
set -eu

mkdir -p database
touch database/database.sqlite

php artisan migrate --force
php artisan db:seed --force
php artisan storage:link --force

exec apache2-foreground
