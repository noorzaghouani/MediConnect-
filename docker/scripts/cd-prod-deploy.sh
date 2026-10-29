#!/bin/bash
# =============================================================================
# cd-prod-deploy.sh — Déploiement en production
#
# Paramètre 1 : IMAGE_REF — image GHCR à déployer
#               ex: ghcr.io/noorzaghouani/mediconnect@sha256:abc123
#               ou: ghcr.io/noorzaghouani/mediconnect:abc1234
#
# Prérequis (depuis l'environnement Jenkins/hôte) :
#   WORKSPACE   Chemin du workspace Jenkins (projet cloné)
#
# Stratégie :
#   - Sauvegarde de l'image courante dans /tmp/previous_digest.txt
#   - Mise à jour du service `app` pour utiliser l'image GHCR
#   - docker compose up -d --no-build app
#   - Exécution des migrations Doctrine
# =============================================================================
set -euo pipefail

IMAGE_REF="${1:-}"
COMPOSE_FILE="${WORKSPACE}/docker-compose.yml"
APP_CONTAINER="mediconnect--app-1"

if [ -z "${IMAGE_REF}" ]; then
    echo "❌ Usage : $0 <IMAGE_REF>"
    echo "   Exemple : $0 ghcr.io/noorzaghouani/mediconnect@sha256:abc123"
    exit 1
fi

# Garantir que l'image a bien le préfixe complet GHCR
if [[ "${IMAGE_REF}" != *"ghcr.io"* ]]; then
    if [[ "${IMAGE_REF}" == *"@"* ]]; then
        DIGEST_ONLY="${IMAGE_REF#*@}"
        IMAGE_REF="ghcr.io/noorzaghouani/mediconnect@${DIGEST_ONLY}"
    elif [[ "${IMAGE_REF}" == *":"* ]]; then
        TAG_ONLY="${IMAGE_REF#*:}"
        IMAGE_REF="ghcr.io/noorzaghouani/mediconnect:${TAG_ONLY}"
    else
        IMAGE_REF="ghcr.io/noorzaghouani/mediconnect:${IMAGE_REF}"
    fi
fi

echo "============================================================"
echo " PRODUCTION DEPLOY — Image: ${IMAGE_REF}"
echo "============================================================"

# --- 0. Sauvegarder le digest de l'ancienne image (pour rollback) --------
echo "[0/4] Sauvegarde de l'image courante pour rollback éventuel..."
CURRENT_IMAGE=$(docker inspect "${APP_CONTAINER}" \
    --format='{{.Config.Image}}' 2>/dev/null || echo "")

if [ -n "${CURRENT_IMAGE}" ]; then
    CURRENT_DIGEST=$(docker inspect "${APP_CONTAINER}" \
        --format='{{index .RepoDigests 0}}' 2>/dev/null \
        || echo "${CURRENT_IMAGE}")
    echo "${CURRENT_DIGEST:-${CURRENT_IMAGE}}" > /tmp/previous_digest.txt
    echo "  ✅ Image précédente sauvegardée : $(cat /tmp/previous_digest.txt)"
else
    echo "  ℹ️  Aucun conteneur app existant — premier déploiement."
    echo "" > /tmp/previous_digest.txt
fi

# --- 1. Mise à jour de l'image dans docker-compose.yml ------------------
echo "[1/4] Mise à jour de l'image dans docker-compose.yml..."

# S'assurer que .env.local existe pour Docker Compose (requis par docker-compose.yml)
if [ ! -f "${WORKSPACE}/.env.local" ]; then
    echo "  ℹ️  Création de .env.local à partir de l'environnement de production existant..."
    docker inspect "${APP_CONTAINER}" --format '{{range .Config.Env}}{{println .}}{{end}}' 2>/dev/null \
        | grep -E '^(APP_ENV|APP_SECRET|APP_DB_PASSWORD|DATABASE_URL|MYSQL_ROOT_PASSWORD|ADMIN_EMAIL|ADMIN_PASSWORD)=' \
        > "${WORKSPACE}/.env.local" || true
    touch "${WORKSPACE}/.env.local"
    echo "  ✅ Fichier .env.local configuré pour Docker Compose."
fi

# Utiliser sed pour remplacer la ligne `build: .` ou l'image existante
# On injecte via variable d'environnement Docker Compose (APP_IMAGE)
export APP_IMAGE="${IMAGE_REF}"

# Créer un override temporaire pour forcer l'image sans modifier docker-compose.yml
OVERRIDE_FILE="${WORKSPACE}/docker-compose.prod-override.yml"
cat > "${OVERRIDE_FILE}" <<EOF
services:
  app:
    image: ${IMAGE_REF}
    build: !reset null
EOF

echo "  ✅ Override créé : ${OVERRIDE_FILE}"

# --- 2. Pull de l'image --------------------------------------------------
echo "[2/4] Pull de l'image GHCR..."
docker pull "${IMAGE_REF}"

# --- 3. Déploiement avec l'override -------------------------------------
echo "[3/4] Démarrage du service app en production..."
docker compose \
    -f "${COMPOSE_FILE}" \
    -f "${OVERRIDE_FILE}" \
    -p mediconnect- \
    up -d \
    --no-build \
    --remove-orphans \
    app

echo "  ✅ Conteneur app redémarré avec la nouvelle image."

# --- 4. Migrations Doctrine ----------------------------------------------
echo "[4/4] Exécution des migrations Doctrine..."
# Attendre que le conteneur soit healthy
sleep 10
docker exec "${APP_CONTAINER}" \
    php bin/console doctrine:migrations:migrate \
    --no-interaction --allow-no-migration

echo ""
echo "✅ Déploiement production terminé — Image: ${IMAGE_REF}"
