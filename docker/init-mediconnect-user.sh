#!/bin/bash
set -e

mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<-EOSQL
    CREATE USER IF NOT EXISTS 'mediconnect_app'@'%' IDENTIFIED BY '${APP_DB_PASSWORD}';
    GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES ON mediconnect.* TO 'mediconnect_app'@'%';
    FLUSH PRIVILEGES;
EOSQL
