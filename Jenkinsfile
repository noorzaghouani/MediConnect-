pipeline {
    agent any

    environment {
        IMAGE_TAG = "mediconnect:${GIT_COMMIT}"
    }

    stages {
        stage('Installer les dépendances') {
            steps {
                sh 'docker run --rm --volumes-from jenkins -w "$WORKSPACE" composer:2 composer install --no-progress --prefer-dist --no-interaction --no-scripts'
            }
        }

        stage('Secrets - Gitleaks') {
            steps {
                sh 'docker run --rm --volumes-from jenkins zricethezav/gitleaks:latest detect --source "$WORKSPACE" --verbose --redact'
            }
        }

        stage('SAST - Semgrep') {
            steps {
                sh 'docker run --rm --volumes-from jenkins semgrep/semgrep semgrep scan --config "p/php" --config "p/owasp-top-ten" --error "$WORKSPACE"'
            }
        }

        stage('SAST - PHPStan') {
            steps {
                sh '''
                    docker run --rm --volumes-from jenkins -w "$WORKSPACE" \
                      -e APP_ENV=test -e APP_SECRET=jenkins_dummy_secret \
                      -e DATABASE_URL="mysql://root:root@127.0.0.1:3306/mediconnect?serverVersion=10.4.32-MariaDB&charset=utf8mb4" \
                      composer:2 vendor/bin/phpstan analyse --no-progress
                '''
            }
        }

        stage('SCA - composer audit') {
            steps {
                sh 'docker run --rm --volumes-from jenkins -w "$WORKSPACE" composer:2 composer audit --locked --no-dev --abandoned=report'
            }
        }

        stage('Tests - PHPUnit') {
            steps {
                sh '''
                    docker run --rm --volumes-from jenkins -w "$WORKSPACE" \
                      -e APP_ENV=test -e APP_SECRET=jenkins_dummy_secret \
                      composer:2 vendor/bin/phpunit --testdox
                '''
            }
        }

        stage('Image Docker + Trivy') {
            steps {
                sh 'docker build -t ${IMAGE_TAG} .'
                sh 'docker run --rm -v /var/run/docker.sock:/var/run/docker.sock aquasec/trivy:latest image --severity HIGH,CRITICAL --exit-code 1 --ignore-unfixed ${IMAGE_TAG}'
            }
        }
    }
}