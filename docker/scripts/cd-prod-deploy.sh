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

# Récupérer les mots de passe de production depuis le conteneur MariaDB existant
DB_CONTAINER="mediconnect--database-1"
DB_PASS=$(docker inspect "${DB_CONTAINER}" --format '{{range .Config.Env}}{{println .}}{{end}}' 2>/dev/null | grep '^APP_DB_PASSWORD=' | cut -d= -f2- || echo "")
ROOT_PASS=$(docker inspect "${DB_CONTAINER}" --format '{{range .Config.Env}}{{println .}}{{end}}' 2>/dev/null | grep '^MYSQL_ROOT_PASSWORD=' | cut -d= -f2- || echo "")
APP_SECRET_VAL=$(docker inspect "${APP_CONTAINER}" --format '{{range .Config.Env}}{{println .}}{{end}}' 2>/dev/null | grep '^APP_SECRET=' | cut -d= -f2- || echo "a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4")

export APP_DB_PASSWORD="${DB_PASS:-11376498nZ}"
export MYSQL_ROOT_PASSWORD="${ROOT_PASS:-11376498nZ}"
export APP_IMAGE="${IMAGE_REF}"

# Créer .env.local avec les credentials valides pour Docker Compose
cat > "${WORKSPACE}/.env.local" <<EOF
APP_ENV=prod
APP_SECRET=${APP_SECRET_VAL}
APP_DB_PASSWORD=${APP_DB_PASSWORD}
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
DATABASE_URL=mysql://mediconnect_app:${APP_DB_PASSWORD}@database:3306/mediconnect?serverVersion=10.4.32-MariaDB&charset=utf8mb4
EOF
echo "  ✅ Fichier .env.local configuré avec succès."

# Créer un override temporaire pour forcer l'image et la DATABASE_URL valide
OVERRIDE_FILE="${WORKSPACE}/docker-compose.prod-override.yml"
cat > "${OVERRIDE_FILE}" <<EOF
services:
  app:
    image: ${IMAGE_REF}
    build: !reset null
    environment:
      - APP_ENV=prod
      - APP_SECRET=${APP_SECRET_VAL}
      - APP_DB_PASSWORD=${APP_DB_PASSWORD}
      - DATABASE_URL=mysql://mediconnect_app:${APP_DB_PASSWORD}@database:3306/mediconnect?serverVersion=10.4.32-MariaDB&charset=utf8mb4
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
