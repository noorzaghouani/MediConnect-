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
OVERRIDE_FILE="${WORKSPACE}/docker-compose.prod-override.yml"
cat > "${OVERRIDE_FILE}" <<EOF
services:
  app:
    image: ${PREVIOUS_IMAGE}
    build: !reset null
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
