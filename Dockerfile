FROM thecodingmachine/php:8.2-v4-fpm-node22 AS builder
ARG VERSION=development

ENV PHP_EXTENSION_LDAP=1
ENV PHP_EXTENSION_INTL=1
ENV PHP_EXTENSION_BCMATH=1
ENV COMPOSER_MEMORY_LIMIT=-1
ENV PHP_EXTENSION_GD=1

COPY . /var/www/html

USER root

RUN npm install \
    && npm run build

RUN composer install --no-scripts

RUN sed -i "s/^laF_version=.*/laF_version=${VERSION}/" .env

RUN tar \
    --exclude='./.github' \
    --exclude='./.git' \
    --exclude='./node_modules' \
    --exclude='./var/cache' \
    --exclude='./var/log' \
    -zcvf /artifact.tgz .

FROM git.h2-invent.com/public-system-design/alpine-php8-cron-webserver:3.20.7
ARG VERSION=development

LABEL version="${VERSION}" \
    Maintainer="H2 invent GmbH" \
    Description="Simple Filemanager" \
    org.opencontainers.version="${VERSION}" \
    org.opencontainers.image.title="Simple Filemanager" \
    org.opencontainers.image.license="AGPLv3" \
    org.opencontainers.image.vendor="H2 invent GmbH" \
    org.opencontainers.image.authors="Andreas Holzmann <support@h2-invent.com>" \
    org.opencontainers.image.source="https://github.com/h2-invent/jitsi-admin" \
    org.opencontainers.image.documentation="https://github.com/H2-invent/Filemanager" \
    org.opencontainers.image.url="https://h2-invent.com"

USER root
RUN apk --no-cache add \
    unzip \
    sqlite \
    php83-sqlite3 \
    php83-pdo_sqlite \
    php83-ldap \
    php83-xmlwriter \
    php83-xsl \
    php83-pcntl \
    php83-posix \
    php83-sockets \
    && rm -rf /var/cache/apk/*

RUN echo "Europe/Berlin" > /etc/timezone

RUN echo "# Docker Cron Jobs" > /var/crontab \
    && echo "SHELL=/bin/sh" >> /var/crontab \
    && echo "TZ=Europe/Berlin" >> /var/crontab \
    && echo "0 * * * * /bin/sh /distributed_cron.sh '/var/www/html/data/cron_lock' 'php /var/www/html/bin/console app:file:expiration'" >> /var/crontab \
    && echo "" >> /var/crontab \
    && chown nobody:nobody /var/crontab

RUN echo "#!/bin/sh" > /docker-entrypoint-init.d/03-symfony.sh \
    && echo "php bin/console cache:clear" >> /docker-entrypoint-init.d/03-symfony.sh \
    && echo "php bin/console doc:mig:mig --no-interaction" >> /docker-entrypoint-init.d/03-symfony.sh \
    && echo "php bin/console cache:clear" >> /docker-entrypoint-init.d/03-symfony.sh \
    && chmod +x /docker-entrypoint-init.d/03-symfony.sh

USER nobody

COPY --from=builder /artifact.tgz artifact.tgz

RUN tar -zxvf artifact.tgz \
    && mkdir data \
    && mkdir -p var/log \
    && mkdir -p var/cache \
    && rm artifact.tgz

ENV nginx_root_directory=/var/www/html/public \
    memory_limit=1024M \
    post_max_size=200M \
    upload_max_filesize=200M \
    date_timezone=Europe/Berlin \
    intl_default_locale=de_DE
