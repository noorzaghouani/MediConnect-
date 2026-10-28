#!/bin/bash
# =============================================================================
# cd-smoke-test.sh — Smoke tests HTTP basiques
#
# Usage : bash docker/scripts/cd-smoke-test.sh <BASE_URL>
# Exemple: bash docker/scripts/cd-smoke-test.sh http://localhost:8001
#
# Retourne exit code 0 si tous les endpoints répondent 200/302.
# Retourne exit code 1 si un endpoint échoue après 5 tentatives.
# =============================================================================
set -euo pipefail

BASE_URL="${1:-http://localhost:8001}"

# Si BASE_URL cible localhost depuis l'intérieur d'un conteneur Jenkins, permuter vers host.docker.internal
if [[ "${BASE_URL}" == *"localhost"* ]] && getent hosts host.docker.internal >/dev/null 2>&1; then
    BASE_URL="${BASE_URL/localhost/host.docker.internal}"
fi

MAX_RETRIES=5
RETRY_DELAY=5
CURL_TIMEOUT=10
APP_CONTAINER="mediconnect-staging-app-1"

# Liste des endpoints à tester (chemin relatif)
ENDPOINTS=(
    "/"
    "/login"
)

echo "============================================================"
echo " SMOKE TESTS — Cible : ${BASE_URL}"
echo "============================================================"

GLOBAL_FAIL=0

for ENDPOINT in "${ENDPOINTS[@]}"; do
    URL="${BASE_URL}${ENDPOINT}"
    SUCCESS=0

    for ATTEMPT in $(seq 1 "${MAX_RETRIES}"); do
        echo -n "  [${ENDPOINT}] Tentative ${ATTEMPT}/${MAX_RETRIES} → "
        HTTP_CODE=$(curl \
            --silent \
            --output /dev/null \
            --write-out "%{http_code}" \
            --max-time "${CURL_TIMEOUT}" \
            --location \
            "${URL}" 2>/dev/null || echo "000")

        if [ "${HTTP_CODE}" = "200" ] || [ "${HTTP_CODE}" = "302" ]; then
            echo "✅ HTTP ${HTTP_CODE}"
            SUCCESS=1
            break
        fi

        # Secours : test directement dans le conteneur
        INTERNAL_CODE=$(docker exec "${APP_CONTAINER}" php -r "
            \$h = @get_headers('http://localhost${ENDPOINT}');
            echo \$h ? substr(\$h[0], 9, 3) : '000';
        " 2>/dev/null || echo "000")

        if [ "${INTERNAL_CODE}" = "200" ] || [ "${INTERNAL_CODE}" = "302" ]; then
            echo "✅ HTTP ${INTERNAL_CODE} (vérifié via conteneur)"
            SUCCESS=1
            break
        fi

        echo "⚠️  HTTP ${HTTP_CODE} (interne: ${INTERNAL_CODE})"
        if [ "${ATTEMPT}" -lt "${MAX_RETRIES}" ]; then
            echo "    Nouvel essai dans ${RETRY_DELAY}s..."
            sleep "${RETRY_DELAY}"
        fi
    done

    if [ "${SUCCESS}" -eq 0 ]; then
        echo "  ❌ ÉCHEC : ${URL} inaccessible après ${MAX_RETRIES} tentatives."
        GLOBAL_FAIL=1
    fi
done

echo ""
if [ "${GLOBAL_FAIL}" -eq 1 ]; then
    echo "❌ Smoke tests ÉCHOUÉS — Le déploiement staging est compromis."
    exit 1
else
    echo "✅ Tous les smoke tests sont passés avec succès."
    exit 0
fi
