pipeline {
    agent any

    options {
        buildDiscarder(logRotator(numToKeepStr: '20'))
        disableConcurrentBuilds()
        timeout(time: 45, unit: 'MINUTES')
    }

    environment {
        IMAGE_TAG    = "mediconnect:${GIT_COMMIT}"
        COMPOSER_IMG = 'composer:2'
        SEMGREP_IMG  = 'semgrep/semgrep:1.182.0'
        SONAR_IMG    = 'sonarsource/sonar-scanner-cli'
        TRIVY_IMG    = 'aquasec/trivy:0.76.1'
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
                sh 'docker build --pull -t ${IMAGE_TAG} .'
            }
        }

        stage('Image - Scan Trivy') {
            steps {
                sh '''
                    docker run --rm \
                      -v /var/run/docker.sock:/var/run/docker.sock \
                      -v trivy-cache:/root/.cache/ \
                      $TRIVY_IMG image --severity HIGH,CRITICAL --exit-code 1 --ignore-unfixed --no-progress ${IMAGE_TAG}
                '''
            }
        }
    }
}
