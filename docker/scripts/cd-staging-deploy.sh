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
PROJECT_NAME="mediconnect-staging"
APP_CONTAINER="mediconnect-staging-app-staging-1"

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
  -p "${PROJECT_NAME}" \
  up -d --remove-orphans \
  --force-recreate

# --- 3. Attente du healthcheck de l'application -------------------------
echo "[3/4] Attente que l'application soit prête (max 90s)..."
RETRIES=18
DELAY=5

# Détection de l'adresse cible (host.docker.internal si container Jenkins, sinon localhost)
TARGET_HOST="localhost"
if getent hosts host.docker.internal >/dev/null 2>&1; then
    TARGET_HOST="host.docker.internal"
fi
TARGET_URL="http://${TARGET_HOST}:8001"

for i in $(seq 1 "${RETRIES}"); do
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 5 "${TARGET_URL}/" 2>/dev/null || echo "000")
    if [ "${HTTP_STATUS}" = "200" ] || [ "${HTTP_STATUS}" = "302" ]; then
        echo "    ✅ Application staging opérationnelle via ${TARGET_URL} (HTTP ${HTTP_STATUS})"
        break
    fi

    # Vérification directe dans le conteneur si curl externe n'a pas encore répondu
    if docker exec "${APP_CONTAINER}" php -r "echo @file_get_contents('http://localhost/') ? 'OK' : '';" 2>/dev/null | grep -q "OK"; then
        echo "    ✅ Application staging opérationnelle (réponse interne du conteneur OK)"
        break
    fi

    if [ "${i}" -eq "${RETRIES}" ]; then
        echo "    ❌ L'application staging ne répond pas après $((RETRIES * DELAY))s"
        docker compose -f "${COMPOSE_FILE}" -p "${PROJECT_NAME}" logs app-staging --tail=50
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
