#!/bin/sh
set -e

echo "Attente de la disponibilite de MariaDB..."
if [ -n "$DATABASE_URL" ]; then
    until php -r '
        $u = parse_url(getenv("DATABASE_URL"));
        $host = $u["host"] ?? "database";
        $port = $u["port"] ?? 3306;
        $user = $u["user"] ?? "mediconnect_app";
        $pass = isset($u["pass"]) ? urldecode($u["pass"]) : getenv("APP_DB_PASSWORD");
        $db   = ltrim($u["path"] ?? "/mediconnect", "/");
        try {
            new PDO("mysql:host=" . $host . ";port=" . $port . ";dbname=" . $db, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            exit(0);
        } catch (\Throwable $e) {
            exit(1);
        }
    ' 2>/dev/null; do
        sleep 2
    done
else
    until php -r "
        try {
            new PDO('mysql:host=database;port=3306;dbname=mediconnect', 'mediconnect_app', getenv('APP_DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            exit(0);
        } catch (\Throwable \$e) {
            exit(1);
        }
    " 2>/dev/null; do
        sleep 2
    done
fi
echo "MariaDB est pret !"

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
php bin/console app:load-specialities --no-interaction || true
php bin/console app:init-admin --no-interaction || true

exec "$@"
