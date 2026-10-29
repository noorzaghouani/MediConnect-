#!/bin/bash
# =============================================================================
# cd-rollback.sh — Rollback automatique de la production
#
# Paramètre 1 : PREVIOUS_IMAGE  — image précédente à restaurer
#               ex: ghcr.io/noorzaghouani/mediconnect@sha256:abc123
# Paramètre 2 : BACKUP_SQL_FILE — chemin du dump SQL à restaurer (optionnel)
#               ex: /opt/mediconnect/backups/db_20241028_120000.sql
#
# Usage :
#   bash docker/scripts/cd-rollback.sh <PREVIOUS_IMAGE> [BACKUP_SQL_FILE]
# =============================================================================
set -euo pipefail

PREVIOUS_IMAGE="${1:-}"
BACKUP_SQL_FILE="${2:-}"
COMPOSE_FILE="${WORKSPACE}/docker-compose.yml"
APP_CONTAINER="mediconnect--app-1"
DB_CONTAINER="mediconnect--database-1"

# Préfixer GHCR si nécessaire
if [[ -n "${PREVIOUS_IMAGE}" ]] && [[ "${PREVIOUS_IMAGE}" != *"ghcr.io"* ]] && [[ "${PREVIOUS_IMAGE}" == *"@"* ]]; then
    PREVIOUS_IMAGE="ghcr.io/noorzaghouani/mediconnect@${PREVIOUS_IMAGE#*@}"
fi

echo "============================================================"
echo " 🔄 ROLLBACK PRODUCTION"
echo "============================================================"

if [ -z "${PREVIOUS_IMAGE}" ]; then
    echo "❌ Aucune image précédente fournie — Rollback impossible."
    exit 1
fi

echo "  Image cible : ${PREVIOUS_IMAGE}"
[ -n "${BACKUP_SQL_FILE}" ] && echo "  Backup SQL   : ${BACKUP_SQL_FILE}"
echo ""

# --- 1. Rollback de l'application ----------------------------------------
echo "[1/3] Rollback de l'image Docker..."

DB_PASS=$(docker inspect "${DB_CONTAINER}" --format '{{range .Config.Env}}{{println .}}{{end}}' 2>/dev/null | grep '^APP_DB_PASSWORD=' | cut -d= -f2- || echo "")
ROOT_PASS=$(docker inspect "${DB_CONTAINER}" --format '{{range .Config.Env}}{{println .}}{{end}}' 2>/dev/null | grep '^MYSQL_ROOT_PASSWORD=' | cut -d= -f2- || echo "")
APP_SECRET_VAL=$(docker inspect "${APP_CONTAINER}" --format '{{range .Config.Env}}{{println .}}{{end}}' 2>/dev/null | grep '^APP_SECRET=' | cut -d= -f2- || echo "a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4")

export APP_DB_PASSWORD="${DB_PASS:-11376498nZ}"
export MYSQL_ROOT_PASSWORD="${ROOT_PASS:-11376498nZ}"

cat > "${WORKSPACE}/.env.local" <<EOF
APP_ENV=prod
APP_SECRET=${APP_SECRET_VAL}
APP_DB_PASSWORD=${APP_DB_PASSWORD}
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
DATABASE_URL=mysql://mediconnect_app:${APP_DB_PASSWORD}@database:3306/mediconnect?serverVersion=10.4.32-MariaDB&charset=utf8mb4
EOF

OVERRIDE_FILE="${WORKSPACE}/docker-compose.prod-override.yml"
cat > "${OVERRIDE_FILE}" <<EOF
services:
  app:
    image: ${PREVIOUS_IMAGE}
    build: !reset null
    environment:
      - APP_ENV=prod
      - APP_SECRET=${APP_SECRET_VAL}
      - APP_DB_PASSWORD=${APP_DB_PASSWORD}
      - DATABASE_URL=mysql://mediconnect_app:${APP_DB_PASSWORD}@database:3306/mediconnect?serverVersion=10.4.32-MariaDB&charset=utf8mb4
EOF

docker pull "${PREVIOUS_IMAGE}" 2>/dev/null || echo "  ⚠️  Pull impossible — utilisation du cache local."

docker compose \
    -f "${COMPOSE_FILE}" \
    -f "${OVERRIDE_FILE}" \
    -p mediconnect- \
    up -d \
    --no-build \
    app

echo "  ✅ Application rollbackée vers : ${PREVIOUS_IMAGE}"

# --- 2. Restauration de la base de données (si fourni) -------------------
if [ -n "${BACKUP_SQL_FILE}" ] && [ -f "${BACKUP_SQL_FILE}" ]; then
    echo "[2/3] Restauration de la base de données depuis ${BACKUP_SQL_FILE}..."
    docker exec -i "${DB_CONTAINER}" \
        sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" mediconnect' \
        < "${BACKUP_SQL_FILE}"
    echo "  ✅ Base de données restaurée."
else
    echo "[2/3] Pas de restauration BDD (aucun fichier fourni ou inexistant)."
fi

# --- 3. Vérification post-rollback ---------------------------------------
echo "[3/3] Vérification du service après rollback..."
sleep 10
TARGET_HOST="localhost"
if getent hosts host.docker.internal >/dev/null 2>&1; then
    TARGET_HOST="host.docker.internal"
fi

HTTP_STATUS=$(curl -k -s -o /dev/null -w "%{http_code}" --max-time 10 -H "Host: mediconnect.com" "https://${TARGET_HOST}/" 2>/dev/null || echo "000")
if [ "${HTTP_STATUS}" = "200" ] || [ "${HTTP_STATUS}" = "302" ]; then
    echo "  ✅ Application opérationnelle après rollback (HTTP ${HTTP_STATUS})."
else
    echo "  ❌ L'application ne répond pas après rollback (HTTP ${HTTP_STATUS})."
    echo "     Intervention manuelle requise !"
    exit 1
fi

echo ""
echo "✅ Rollback terminé avec succès."
