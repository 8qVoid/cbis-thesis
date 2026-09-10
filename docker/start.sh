#!/bin/sh
set -eu

php artisan migrate --force
php artisan db:seed --force
php artisan storage:link --force

exec apache2-foreground
