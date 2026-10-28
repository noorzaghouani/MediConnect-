pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '20'))
        disableConcurrentBuilds()
        timeout(time: 60, unit: 'MINUTES')
    }

    environment {
        IMAGE_TAG    = "mediconnect:${GIT_COMMIT}"
        COMPOSER_IMG = 'composer:2'
        SEMGREP_IMG  = 'semgrep/semgrep:1.182.0'
        SONAR_IMG    = 'sonarsource/sonar-scanner-cli'
        TRIVY_IMG    = 'aquasec/trivy:0.76.1'
        // CD — Image publiée sur GitHub Container Registry
        GHCR_IMAGE   = 'ghcr.io/noorzaghouani/mediconnect'
    }

    stages {

        stage('Installer les dépendances') {
            steps {
                sh '''
                    docker run --rm --volumes-from jenkins -w "$WORKSPACE" \
                      -v composer-cache:/tmp/cache \
                      $COMPOSER_IMG composer install --no-progress --prefer-dist --no-interaction --no-scripts
                '''
            }
        }

        stage('Secrets - Gitleaks') {
            steps {
                sh 'docker run --rm --volumes-from jenkins zricethezav/gitleaks:latest detect --source "$WORKSPACE" --verbose --redact'
            }
        }

        stage('Analyses sécurité & qualité') {
            parallel {
                stage('SAST - Semgrep') {
                    steps {
                        sh '''
                            docker run --rm --volumes-from jenkins -w "$WORKSPACE" $SEMGREP_IMG \
                              semgrep scan --config p/php --config p/owasp-top-ten \
                              --exclude .github --exclude vendor --exclude var --exclude node_modules \
                              --metrics=off --error .
                        '''
                    }
                }

                stage('SAST - PHPStan') {
                    steps {
                        sh '''
                            docker run --rm --volumes-from jenkins -w "$WORKSPACE" \
                              -e APP_ENV=test -e APP_SECRET=jenkins_dummy_secret \
                              -e DATABASE_URL="mysql://root:root@127.0.0.1:3306/mediconnect?serverVersion=10.4.32-MariaDB&charset=utf8mb4" \
                              $COMPOSER_IMG vendor/bin/phpstan analyse --no-progress --memory-limit=1G
                        '''
                    }
                }

                stage('SCA - composer audit') {
                    steps {
                        sh '''
                            docker run --rm --volumes-from jenkins -w "$WORKSPACE" \
                              $COMPOSER_IMG composer audit --locked --no-dev --abandoned=report
                        '''
                    }
                }
            }
        }

        stage('Qualité - SonarCloud') {
            steps {
                withCredentials([string(credentialsId: 'sonar-token', variable: 'SONAR_TOKEN')]) {
                    sh '''
                        docker run --rm --volumes-from jenkins -w "$WORKSPACE" \
                          -e SONAR_HOST_URL=https://sonarcloud.io -e SONAR_TOKEN=$SONAR_TOKEN \
                          $SONAR_IMG
                    '''
                }
            }
        }

        stage('Tests Unitaires') {
            steps {
                sh '''
                    cp -n .env.example .env || touch .env
                    docker run --rm --volumes-from jenkins -w "$WORKSPACE" \
                      -e APP_ENV=test -e APP_SECRET=jenkins_dummy_secret \
                      -e MESSENGER_TRANSPORT_DSN="doctrine://default?auto_setup=0" \
                      -e SYMFONY_DEPRECATIONS_HELPER=999999 \
                      $COMPOSER_IMG vendor/bin/phpunit --testsuite=Unit --testdox
                '''
            }
        }

        stage('Tests Integration BDD') {
            steps {
                sh '''
                    docker run --rm --volumes-from jenkins -w "$WORKSPACE" \
                      -e APP_ENV=test -e APP_SECRET=jenkins_dummy_secret \
                      -e MESSENGER_TRANSPORT_DSN="doctrine://default?auto_setup=0" \
                      -e SYMFONY_DEPRECATIONS_HELPER=999999 \
                      $COMPOSER_IMG vendor/bin/phpunit --testsuite=Integration --testdox
                '''
            }
        }

        stage('Tests Fonctionnels') {
            steps {
                sh '''
                    docker run --rm --volumes-from jenkins -w "$WORKSPACE" \
                      -e APP_ENV=test -e APP_SECRET=jenkins_dummy_secret \
                      -e MESSENGER_TRANSPORT_DSN="doctrine://default?auto_setup=0" \
                      -e SYMFONY_DEPRECATIONS_HELPER=999999 \
                      $COMPOSER_IMG vendor/bin/phpunit --testsuite=Functional --testdox
                '''
            }
        }

        stage('Image - Build Docker') {
            steps {
                sh 'docker build -t ${IMAGE_TAG} .'
            }
        }

        stage('Image - Scan Trivy') {
            steps {
                sh '''
                    docker run --rm \
                      -v /var/run/docker.sock:/var/run/docker.sock \
                      -v trivy-cache:/root/.cache/ \
                      $TRIVY_IMG image --scanners vuln --severity HIGH,CRITICAL --exit-code 1 --ignore-unfixed --no-progress ${IMAGE_TAG}
                '''
            }
        }

        // ══════════════════════════════════════════════════════════════════
        //  PIPELINE CD
        //  Les stages CI ci-dessus ne sont PAS modifiés.
        // ══════════════════════════════════════════════════════════════════

        // ── Étape 1 : Publication GHCR ────────────────────────────────────
        stage('CD - Publication GHCR') {
            steps {
                withCredentials([string(credentialsId: 'ghcr-token', variable: 'GHCR_TOKEN')]) {
                    sh '''
                        echo "$GHCR_TOKEN" | docker login ghcr.io -u noorzaghouani --password-stdin
                        docker tag ${IMAGE_TAG} ${GHCR_IMAGE}:${GIT_COMMIT}
                        docker tag ${IMAGE_TAG} ${GHCR_IMAGE}:latest
                        docker push ${GHCR_IMAGE}:${GIT_COMMIT}
                        docker push ${GHCR_IMAGE}:latest
                        # Récupérer le digest SHA256 immuable après push
                        docker inspect --format=\'{{index .RepoDigests 0}}\' ${GHCR_IMAGE}:${GIT_COMMIT} \
                            > /tmp/image_digest.txt 2>/dev/null \
                          || docker inspect --format=\'{{.Id}}\' ${IMAGE_TAG} \
                            > /tmp/image_digest.txt
                        echo "Digest publié : $(cat /tmp/image_digest.txt)"
                    '''
                }
            }
        }

        // ── Étape 2 : SBOM & preuves de sécurité ─────────────────────────
        stage('CD - SBOM & Preuves Sécurité') {
            steps {
                sh '''
                    mkdir -p $WORKSPACE/sbom
                    docker run --rm \
                      -v /var/run/docker.sock:/var/run/docker.sock \
                      -v trivy-cache:/root/.cache/ \
                      --volumes-from jenkins \
                      $TRIVY_IMG image \
                      --format spdx-json \
                      --output $WORKSPACE/sbom/sbom-${GIT_COMMIT}.json \
                      ${IMAGE_TAG}
                    echo "SBOM généré : sbom/sbom-${GIT_COMMIT}.json"
                '''
                archiveArtifacts artifacts: "sbom/sbom-${env.GIT_COMMIT}.json", allowEmptyArchive: true
            }
        }

        // ── Étape 3+4 : Déploiement Staging + Migrations ─────────────────
        stage('CD - Déploiement Staging') {
            environment {
                STAGING_DB_PASSWORD = credentials('staging-db-password')
                STAGING_APP_SECRET  = credentials('staging-app-secret')
                // Mot de passe root staging : dérivé ou identique au staging-db-password
                STAGING_DB_ROOT_PASSWORD = credentials('staging-db-password')
            }
            steps {
                withCredentials([string(credentialsId: 'ghcr-token', variable: 'GHCR_TOKEN')]) {
                    sh '''
                        echo "$GHCR_TOKEN" | docker login ghcr.io -u noorzaghouani --password-stdin
                        bash $WORKSPACE/docker/scripts/cd-staging-deploy.sh
                    '''
                }
            }
        }

        // ── Étape 5 : Smoke Tests Staging ────────────────────────────────
        stage('CD - Smoke Tests Staging') {
            steps {
                sh 'bash $WORKSPACE/docker/scripts/cd-smoke-test.sh http://localhost:8001'
            }
        }

        // ── Étape 6 : DAST OWASP ZAP ─────────────────────────────────────
        stage('CD - DAST (OWASP ZAP)') {
            steps {
                sh '''
                    mkdir -p $WORKSPACE/zap-reports
                    # -I  : ne pas faire échouer le build sur les alertes en-dessous du seuil
                    # -l HIGH : rapporter seulement HIGH et au-dessus
                    docker run --rm \
                      --network host \
                      --volumes-from jenkins \
                      ghcr.io/zaproxy/zaproxy:stable \
                      zap-baseline.py \
                      -t http://localhost:8001 \
                      -r $WORKSPACE/zap-reports/zap-report.html \
                      -l HIGH \
                      -z "-config alert.maxInstances=10" || ZAP_EXIT=$?
                    # ZAP exit 2 = alertes WARN seulement → toléré
                    # ZAP exit 1 = alertes HIGH/CRITICAL → bloquant
                    if [ "${ZAP_EXIT:-0}" -eq 1 ]; then
                        echo "❌ ZAP a trouvé des alertes HIGH ou CRITICAL — pipeline bloqué."
                        exit 1
                    fi
                '''
                archiveArtifacts artifacts: 'zap-reports/zap-report.html', allowEmptyArchive: true
            }
        }

        // ── Étape 7 : Gate d'Approbation Manuelle ────────────────────────
        stage('CD - Approbation Manuelle') {
            steps {
                timeout(time: 30, unit: 'MINUTES') {
                    input message: '🚀 Déployer en PRODUCTION ?',
                          ok: '✅ Approuver le déploiement',
                          submitter: 'admin'
                }
            }
        }

        // ── Étape 8 : Sauvegarde Production ──────────────────────────────
        stage('CD - Sauvegarde Production') {
            steps {
                sh '''
                    BACKUP_FILE=$(bash $WORKSPACE/docker/scripts/cd-backup-prod.sh | tail -1)
                    echo "BACKUP_SQL_FILE=${BACKUP_FILE}" > /tmp/cd_env.properties
                    echo "Sauvegarde SQL : ${BACKUP_FILE}"
                '''
            }
        }

        // ── Étape 9 : Déploiement Production ─────────────────────────────
        stage('CD - Déploiement Production') {
            steps {
                withCredentials([string(credentialsId: 'ghcr-token', variable: 'GHCR_TOKEN')]) {
                    sh '''
                        echo "$GHCR_TOKEN" | docker login ghcr.io -u noorzaghouani --password-stdin
                        IMAGE_DIGEST=$(cat /tmp/image_digest.txt)
                        bash $WORKSPACE/docker/scripts/cd-prod-deploy.sh "${IMAGE_DIGEST}"
                    '''
                }
            }
        }

        // ── Étape 10 : Health Check Post-Déploiement + Rollback auto ─────
        stage('CD - Health Check Production') {
            steps {
                script {
                    def maxRetries = 6
                    def delay = 10
                    def healthy = false

                    for (int i = 1; i <= maxRetries; i++) {
                        def status = sh(
                            script: "curl -s -o /dev/null -w '%{http_code}' --max-time 10 https://mediconnect.com/ 2>/dev/null || echo '000'",
                            returnStdout: true
                        ).trim()

                        echo "Health check [${i}/${maxRetries}] → HTTP ${status}"

                        if (status == '200' || status == '302') {
                            healthy = true
                            echo '✅ Production opérationnelle.'
                            break
                        }
                        if (i < maxRetries) {
                            sleep(delay)
                        }
                    }

                    if (!healthy) {
                        echo '❌ Health check échoué — Déclenchement du rollback automatique...'
                        def previousDigest = sh(
                            script: 'cat /tmp/previous_digest.txt 2>/dev/null || echo ""',
                            returnStdout: true
                        ).trim()
                        def backupFile = sh(
                            script: 'grep BACKUP_SQL_FILE /tmp/cd_env.properties 2>/dev/null | cut -d= -f2 || echo ""',
                            returnStdout: true
                        ).trim()

                        if (previousDigest) {
                            sh "bash $WORKSPACE/docker/scripts/cd-rollback.sh '${previousDigest}' '${backupFile}'"
                        } else {
                            echo '⚠️  Pas d\'image précédente — rollback manuel requis.'
                        }
                        currentBuild.result = 'FAILURE'
                        error('Health check production échoué — rollback effectué.')
                    }
                }
            }
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    //  POST-PIPELINE
    //  Étape 11 : Tag Git + Notification
    //  Étape 12 : Nettoyage
    // ══════════════════════════════════════════════════════════════════════
    post {
        success {
            // Étape 11a — Tag Git versionnée
            withCredentials([string(credentialsId: 'ghcr-token', variable: 'GH_TOKEN')]) {
                sh '''
                    git config user.email "jenkins@mediconnect.com"
                    git config user.name "Jenkins CI/CD"
                    SHORT_COMMIT=$(git rev-parse --short HEAD)
                    VERSION="v$(date +%Y.%m.%d)-${SHORT_COMMIT}"
                    git tag -a "$VERSION" -m "Release $VERSION — Deploy automatique depuis Jenkins build #${BUILD_NUMBER}"
                    git push https://$GH_TOKEN@github.com/noorzaghouani/MediConnect-.git "$VERSION" || true
                    echo "Tag git créé : $VERSION"
                '''
            }
            // Étape 11b — Notification succès
            echo "═══════════════════════════════════════════════════════════"
            echo "✅ DÉPLOIEMENT EN PRODUCTION RÉUSSI (Build #${BUILD_NUMBER})"
            echo "   Image : ${GHCR_IMAGE}:${GIT_COMMIT}"
            echo "═══════════════════════════════════════════════════════════"
            // Étape 12 — Nettoyage des images anciennes et backups périmés
            sh '''
                echo "Nettoyage des images Docker de plus de 7 jours..."
                docker image prune -f --filter "until=168h" || true
                echo "Rotation des fichiers de backup de plus de 7 jours..."
                find /opt/mediconnect/backups -name "*.sql" -mtime +7 -delete 2>/dev/null || true
                find /opt/mediconnect/backups -name "*.tar.gz" -mtime +7 -delete 2>/dev/null || true
                echo "Nettoyage terminé."
            '''
        }
        failure {
            // Notification échec
            echo "═══════════════════════════════════════════════════════════"
            echo "❌ ÉCHEC DU PIPELINE (Build #${BUILD_NUMBER})"
            echo "   Commit : ${GIT_COMMIT}"
            echo "   Consultez la console Jenkins pour les détails de l'erreur."
            echo "═══════════════════════════════════════════════════════════"
        }
        always {
            // Nettoyage staging (qu'il y ait eu succès ou échec)
            sh '''
                echo "Teardown de l\'environnement staging..."
                docker compose \
                    -f $WORKSPACE/docker-compose.staging.yml \
                    -p mediconnect-staging \
                    down --volumes --remove-orphans 2>/dev/null || true
                # Supprimer l\'override prod temporaire
                rm -f $WORKSPACE/docker-compose.prod-override.yml || true
                echo "Teardown staging terminé."
            '''
        }
    }
}