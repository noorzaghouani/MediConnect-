#!/bin/bash
# =============================================================================
# cd-backup-prod.sh — Sauvegarde complète de la production avant déploiement
#
# Effectue :
#   1. mysqldump depuis le conteneur MariaDB prod
#   2. Archive tar.gz du dossier var/uploads si existant
#   3. Rotation : conserve seulement les 7 derniers backups SQL
#
# Usage : bash docker/scripts/cd-backup-prod.sh
# Stdout (dernière ligne) : chemin absolu du fichier SQL créé (pour rollback)
# =============================================================================
set -euo pipefail

BACKUP_DIR="/opt/mediconnect/backups"
DB_CONTAINER="mediconnect--database-1"
UPLOADS_SRC="${WORKSPACE:-/opt/mediconnect}/var/uploads"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
SQL_FILE="${BACKUP_DIR}/db_${TIMESTAMP}.sql"
TAR_FILE="${BACKUP_DIR}/uploads_${TIMESTAMP}.tar.gz"
MAX_BACKUPS=7

echo "============================================================"
echo " BACKUP PRODUCTION — ${TIMESTAMP}"
echo "============================================================"

# --- Création du répertoire de sauvegarde --------------------------------
mkdir -p "${BACKUP_DIR}"

# --- 1. Dump de la base de données ---------------------------------------
echo "[1/3] Dump MariaDB depuis ${DB_CONTAINER}..."
docker exec "${DB_CONTAINER}" \
    sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers --databases mediconnect' \
    > "${SQL_FILE}"

if [ ! -s "${SQL_FILE}" ]; then
    echo "❌ Le dump SQL est vide — Abandon."
    rm -f "${SQL_FILE}"
    exit 1
fi

SQL_SIZE=$(du -sh "${SQL_FILE}" | cut -f1)
echo "  ✅ Dump créé : ${SQL_FILE} (${SQL_SIZE})"

# --- 2. Archive des uploads ----------------------------------------------
echo "[2/3] Archive du dossier uploads..."
if [ -d "${UPLOADS_SRC}" ] && [ "$(ls -A "${UPLOADS_SRC}" 2>/dev/null)" ]; then
    tar -czf "${TAR_FILE}" -C "$(dirname "${UPLOADS_SRC}")" "$(basename "${UPLOADS_SRC}")"
    TAR_SIZE=$(du -sh "${TAR_FILE}" | cut -f1)
    echo "  ✅ Archive créée : ${TAR_FILE} (${TAR_SIZE})"
else
    echo "  ℹ️  Dossier uploads absent ou vide — skipped."
fi

# --- 3. Rotation : garder seulement les MAX_BACKUPS derniers SQL ---------
echo "[3/3] Rotation des backups (conservation des ${MAX_BACKUPS} derniers)..."
BACKUP_COUNT=$(find "${BACKUP_DIR}" -maxdepth 1 -name "db_*.sql" | wc -l)
if [ "${BACKUP_COUNT}" -gt "${MAX_BACKUPS}" ]; then
    TO_DELETE=$((BACKUP_COUNT - MAX_BACKUPS))
    find "${BACKUP_DIR}" -maxdepth 1 -name "db_*.sql" \
        | sort \
        | head -n "${TO_DELETE}" \
        | xargs rm -f
    echo "  ✅ ${TO_DELETE} ancien(s) backup(s) SQL supprimé(s)."
    find "${BACKUP_DIR}" -maxdepth 1 -name "uploads_*.tar.gz" \
        | sort \
        | head -n "${TO_DELETE}" \
        | xargs rm -f 2>/dev/null || true
else
    echo "  ℹ️  Aucune rotation nécessaire (${BACKUP_COUNT}/${MAX_BACKUPS} backups)."
fi

echo ""
echo "✅ Sauvegarde terminée."
# IMPORTANT : Le chemin du SQL est affiché en dernière ligne pour capture Jenkins
echo "${SQL_FILE}"
