#!/bin/sh
set -e

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
php bin/console app:load-specialities --no-interaction || true
php bin/console app:init-admin --no-interaction || true

chown -R www-data:www-data /var/www/html/var

exec "$@"
