FROM composer:2 AS vendor-builder
WORKDIR /app
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

FROM php:8.2-apache AS app

RUN apt-get update && apt-get upgrade -y openssl libssl3t64 \
    && apt-get install -y --no-install-recommends \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip opcache \
    && apt-get purge -y --auto-remove libicu-dev libzip-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . .
COPY --from=vendor-builder /app/vendor ./vendor

RUN mkdir -p /var/www/html/var/uploads/diplomes \
             /var/www/html/var/cache \
             /var/www/html/var/log \
    && chown -R www-data:www-data /var/www/html

COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

USER www-data
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"] # nosemgrep: dockerfile.security.missing-user-entrypoint
CMD ["apache2-foreground"] # nosemgrep: dockerfile.security.missing-user