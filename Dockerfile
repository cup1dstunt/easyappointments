# Production image for this fork: builds the frontend assets and the composer
# dependencies at image build time, so the container runs without a source
# checkout. config.php is generated from environment variables on start (see
# deploy/docker-entrypoint.sh). The docker/ folder and docker-compose.yml in
# the repository root are the upstream development setup, not this image.

FROM node:22-bookworm-slim AS assets

WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY gulpfile.js babel.config.json ./
COPY assets ./assets
RUN npx gulp compile

FROM php:8.3-apache

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN install-php-extensions gd intl ldap mysqli pdo_mysql zip exif bcmath opcache \
    && a2enmod rewrite headers remoteip \
    && printf 'RemoteIPHeader X-Forwarded-For\nRemoteIPInternalProxy 10.0.0.0/8 172.16.0.0/12 192.168.0.0/16\n' \
        > /etc/apache2/conf-enabled/remoteip.conf \
    && echo 'ServerTokens Prod' > /etc/apache2/conf-enabled/z-security.conf \
    && echo 'ServerSignature Off' >> /etc/apache2/conf-enabled/z-security.conf

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ENV TZ=UTC

COPY deploy/php.ini /usr/local/etc/php/conf.d/99-easyappointments.ini

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader \
    && rm -rf /root/.composer

COPY --chown=www-data:www-data . .
COPY --from=assets --chown=www-data:www-data /build/assets ./assets

RUN composer dump-autoload --no-dev --optimize --no-interaction \
    && composer run-script cleanup-vendor --no-interaction \
    && rm -rf docker docker-compose.yml node_modules tests .github .run \
    && cp -a storage /usr/src/easyappointments-storage

COPY --chmod=0755 deploy/docker-entrypoint.sh /usr/local/bin/easyappointments-entrypoint

VOLUME /var/www/html/storage

ENTRYPOINT ["easyappointments-entrypoint"]
CMD ["apache2-foreground"]
