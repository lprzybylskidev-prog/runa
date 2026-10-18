# syntax=docker/dockerfile:1
FROM composer:2.10.3 AS composer
FROM node:24.22.0-bookworm-slim AS node

FROM php:8.4.26-fpm-bookworm AS runtime
ARG RUNA_UID=1000
ARG RUNA_GID=1000
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libpq5 libicu72 libzip4 libonig5 libfcgi-bin \
        libpq-dev libicu-dev libzip-dev libonig-dev \
    && docker-php-ext-install -j2 pdo_pgsql intl bcmath opcache pcntl zip mbstring \
    && apt-get purge -y --auto-remove libpq-dev libicu-dev libzip-dev libonig-dev \
    && rm -rf /var/lib/apt/lists/* \
    && groupadd --gid "${RUNA_GID}" runa \
    && useradd --uid "${RUNA_UID}" --gid runa --create-home --shell /bin/bash runa \
    && mkdir -p /workspace /home/runa/.cache/composer /home/runa/.config/composer \
    && chown -R runa:runa /workspace /home/runa \
    && rm /usr/local/etc/php-fpm.d/www.conf /usr/local/etc/php-fpm.d/docker.conf
RUN apt-get purge -y --auto-remove $PHPIZE_DEPS && rm -rf /var/lib/apt/lists/*
COPY docker/php/pool.conf /usr/local/etc/php-fpm.d/runa.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/runa.ini
COPY --chmod=755 docker/php/healthcheck /usr/local/bin/runa-fpm-health
WORKDIR /workspace
USER runa
HEALTHCHECK --interval=10s --timeout=3s --start-period=10s --retries=5 CMD ["runa-fpm-health"]
CMD ["php-fpm", "-F"]

FROM runtime AS development-toolchain
USER root
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && apt-get update \
    && apt-get install -y --no-install-recommends git openssh-client curl unzip zip python3 procps ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && npm install --global corepack@0.36.0 @openai/codex@0.163.0 \
    && corepack enable \
    && COREPACK_HOME=/opt/corepack corepack install --global pnpm@12.14.0 \
    && chown -R runa:runa /opt/corepack
ENV COREPACK_HOME=/opt/corepack
ENV COMPOSER_HOME=/home/runa/.config/composer
USER runa
RUN pnpm --version

FROM development-toolchain AS development
USER root
RUN apt-get update \
    && apt-get install -y --no-install-recommends $PHPIZE_DEPS \
    && pecl install xdebug-3.5.3 \
    && docker-php-ext-enable xdebug \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/* /tmp/pear
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/zz-runa-xdebug.ini
RUN chown runa:runa /usr/local/etc/php/conf.d/zz-runa-xdebug.ini
ENV PLAYWRIGHT_BROWSERS_PATH=/opt/playwright
RUN npx --yes playwright@1.64.1 install --with-deps chromium firefox \
    && chmod -R a+rX /opt/playwright \
    && rm -rf /var/lib/apt/lists/* /root/.npm
USER runa

# Production keeps the same PHP version/capabilities on the approved Alpine candidate.
# Development retains its browser-compatible Debian toolchain.
FROM php:8.4.26-fpm-alpine3.24@sha256:78cd8de9970a9cd6ff4d98860a94eb5bf37dd2d5776b4785562dbfad01c29d5a AS production-runtime
ARG RUNA_UID=1000
ARG RUNA_GID=1000
RUN apk add --no-cache libpq icu-libs icu-data-full libzip oniguruma fcgi tzdata \
    && apk add --no-cache --virtual .runa-build-deps \
        $PHPIZE_DEPS libpq-dev icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install -j2 pdo_pgsql intl bcmath opcache pcntl zip mbstring \
    && apk del .runa-build-deps \
    && addgroup -g "${RUNA_GID}" -S runa \
    && adduser -u "${RUNA_UID}" -G runa -D -h /home/runa -s /bin/sh runa \
    && mkdir -p /workspace /home/runa/.cache/composer /home/runa/.config/composer \
    && chown -R runa:runa /workspace /home/runa \
    && rm /usr/local/etc/php-fpm.d/www.conf /usr/local/etc/php-fpm.d/docker.conf
COPY docker/php/pool.conf /usr/local/etc/php-fpm.d/runa.conf
COPY docker/php/php.ini /usr/local/etc/php/conf.d/runa.ini
COPY --chmod=755 docker/php/healthcheck /usr/local/bin/runa-fpm-health
WORKDIR /workspace
USER runa
HEALTHCHECK --interval=10s --timeout=3s --start-period=10s --retries=5 CMD ["runa-fpm-health"]
CMD ["php-fpm", "-F"]

FROM production-runtime AS php-dependencies
USER root
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
RUN apk add --no-cache unzip \
    && mkdir -p /tmp/runa-build && chown runa:runa /tmp/runa-build
USER runa
ENV COMPOSER_HOME=/tmp/composer
WORKDIR /tmp/runa-build
COPY --chown=runa:runa composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction
COPY --chown=runa:runa app/ app/
COPY --chown=runa:runa database/ database/
RUN composer dump-autoload --no-dev --no-scripts --optimize

FROM node AS frontend-build
WORKDIR /build
RUN npm install --global corepack@0.36.0 && corepack enable && corepack install --global pnpm@12.14.0
COPY package.json pnpm-lock.yaml pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile
COPY resources/ resources/
COPY vite.config.mjs tsconfig.json VERSION ./
RUN pnpm run build

FROM production-runtime AS production
COPY --from=production-runtime /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
ENV APP_ENV=production APP_DEBUG=false KNOWLEDGE_ENABLED=false
COPY --chmod=755 docker/php/production-entrypoint /usr/local/bin/runa-production-entrypoint
ENTRYPOINT ["runa-production-entrypoint"]
CMD ["php-fpm", "-F"]
# Laravel command discovery reads the application namespace from this metadata.
COPY --chown=runa:runa composer.json /workspace/composer.json
COPY --chown=runa:runa --from=php-dependencies /tmp/runa-build/vendor/ /workspace/vendor/
COPY --chown=runa:runa app/ /workspace/app/
COPY --chown=runa:runa bootstrap/ /workspace/bootstrap/
COPY --chown=runa:runa config/ /workspace/config/
COPY --chown=runa:runa database/ /workspace/database/
COPY --chown=runa:runa routes/ /workspace/routes/
COPY --chown=runa:runa lang/ /workspace/lang/
COPY --chown=runa:runa public/index.php /workspace/public/index.php
COPY --chown=runa:runa --from=frontend-build /build/public/build/ /workspace/public/build/
RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/framework/health storage/logs
COPY --chown=runa:runa artisan /workspace/artisan

FROM nginx:1.31.7-alpine-slim@sha256:ebd058041a732a2b2dc0b606e34572dd8e0034de6a4aa4d50d8c13a240ac6005 AS production-nginx
COPY docker/nginx/production.conf /etc/nginx/nginx.conf
COPY --from=frontend-build /build/public/build/ /workspace/public/build/
USER nginx
ENTRYPOINT ["nginx", "-g", "daemon off;"]
HEALTHCHECK --interval=10s --timeout=3s --retries=5 CMD wget -qO- http://127.0.0.1:8080/api/ready || exit 1
