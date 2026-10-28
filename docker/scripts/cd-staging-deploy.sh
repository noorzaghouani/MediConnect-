#!/bin/bash
# =============================================================================
# cd-staging-deploy.sh — Déploiement de l'environnement de staging
#
# Prérequis (variables d'environnement attendues depuis Jenkins) :
#   GHCR_IMAGE          ex: ghcr.io/noorzaghouani/mediconnect
#   GIT_COMMIT          SHA du commit courant
#   STAGING_DB_PASSWORD Mot de passe de la BDD staging
#   STAGING_APP_SECRET  APP_SECRET Symfony pour staging
#   WORKSPACE           Chemin du workspace Jenkins
#
# Usage : bash docker/scripts/cd-staging-deploy.sh
# =============================================================================
set -euo pipefail

COMPOSE_FILE="${WORKSPACE}/docker-compose.staging.yml"
APP_CONTAINER="mediconnect--app-staging-1"

echo "============================================================"
echo " STAGING DEPLOY — Image: ${GHCR_IMAGE}:${GIT_COMMIT}"
echo "============================================================"

# --- 1. Pull de la nouvelle image depuis GHCR ----------------------------
echo "[1/4] Pull de l'image GHCR..."
docker pull "${GHCR_IMAGE}:${GIT_COMMIT}"

# --- 2. Démarrage des services staging -----------------------------------
echo "[2/4] Démarrage des conteneurs staging..."
docker compose \
  -f "${COMPOSE_FILE}" \
  -p mediconnect- \
  up -d --remove-orphans \
  --force-recreate

# --- 3. Attente du healthcheck de l'application -------------------------
echo "[3/4] Attente que l'application soit prête (max 90s)..."
RETRIES=18
DELAY=5
for i in $(seq 1 "${RETRIES}"); do
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 http://localhost:8001/ 2>/dev/null || echo "000")
    if [ "${HTTP_STATUS}" = "200" ] || [ "${HTTP_STATUS}" = "302" ]; then
        echo "    ✅ Application staging opérationnelle (HTTP ${HTTP_STATUS})"
        break
    fi
    if [ "${i}" -eq "${RETRIES}" ]; then
        echo "    ❌ L'application staging ne répond pas après $((RETRIES * DELAY))s (HTTP ${HTTP_STATUS})"
        docker compose -f "${COMPOSE_FILE}" -p mediconnect- logs app-staging --tail=50
        exit 1
    fi
    echo "    ⏳ Tentative ${i}/${RETRIES} — HTTP ${HTTP_STATUS} — attente ${DELAY}s..."
    sleep "${DELAY}"
done

# --- 4. Exécution des migrations Doctrine --------------------------------
echo "[4/4] Exécution des migrations Doctrine sur staging..."
docker exec "${APP_CONTAINER}" \
    php bin/console doctrine:migrations:migrate \
    --no-interaction --allow-no-migration

echo ""
echo "✅ Déploiement staging terminé avec succès."
