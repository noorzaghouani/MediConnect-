#!/bin/sh
set -e

echo "Attente de la disponibilite de MariaDB..."
until php -r "new PDO('mysql:host=database;port=3306;dbname=mediconnect', 'mediconnect_app', getenv('APP_DB_PASSWORD'));" 2>/dev/null; do
    sleep 2
done
echo "MariaDB est pret !"

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
php bin/console app:load-specialities --no-interaction || true
php bin/console app:init-admin --no-interaction || true

exec "$@"
