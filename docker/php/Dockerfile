# syntax=docker/dockerfile:1
FROM composer:2.10.3 AS composer
FROM node:24.22.0-bookworm-slim AS node

FROM php:8.4.27-fpm-bookworm@sha256:df5a5113e1179ddf4560cd15727eb3812f6457829756d2a96c6c11bb4c250089 AS runtime
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
ENV PYTHONPYCACHEPREFIX=/workspace/.runtime/python/pycache
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
FROM php:8.4.27-fpm-alpine3.24@sha256:93aa3f61216b73cc6f878235c9772849449f4efc18dfe3d6678eed645076c00d AS production-runtime
ARG RUNA_UID=1000
ARG RUNA_GID=1000
RUN apk add --upgrade --no-cache 'curl>=8.23.0-r0' 'libcurl>=8.23.0-r0' 'brotli-libs>=1.2.1-r0' \
    && apk add --no-cache libpq icu-libs icu-data-full libzip oniguruma fcgi tzdata \
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
    && mkdir -p /tmp/runa-build/src && chown -R runa:runa /tmp/runa-build
USER runa
ENV COMPOSER_HOME=/tmp/composer
WORKDIR /tmp/runa-build/src
COPY --chown=runa:runa src/composer.json src/composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --no-interaction
COPY --chown=runa:runa src/app/ app/
COPY --chown=runa:runa src/runa/Console/ runa/Console/
COPY --chown=runa:runa src/runa/Contracts/ runa/Contracts/
COPY --chown=runa:runa src/runa/Database/ runa/Database/
COPY --chown=runa:runa src/runa/Foundation/ runa/Foundation/
COPY --chown=runa:runa src/runa/Http/ runa/Http/
COPY --chown=runa:runa src/runa/Providers/ runa/Providers/
COPY --chown=runa:runa src/runa/Support/ runa/Support/
COPY --chown=runa:runa src/runa/Configuration/ runa/Configuration/
COPY --chown=runa:runa src/runa/Identity/ runa/Identity/
COPY --chown=runa:runa src/runa/Integration/ runa/Integration/
COPY --chown=runa:runa src/runa/Logging/ runa/Logging/
COPY --chown=runa:runa src/runa/Mail/ runa/Mail/
COPY --chown=runa:runa src/runa/ModuleFramework/ runa/ModuleFramework/
COPY --chown=runa:runa src/runa/Runtime/ runa/Runtime/
COPY --chown=runa:runa src/runa/modules/ runa/modules/
COPY --chown=runa:runa src/runa/database/ runa/database/
RUN composer dump-autoload --no-dev --no-scripts --optimize \
    && rm -rf app/resources/js app/resources/css app/resources/index.html
COPY --chown=runa:runa src/runa/config/branding.php runa/config/branding.php
COPY --chown=runa:runa src/runa/resources/errors/ runa/resources/errors/
COPY --chown=runa:runa src/runa/resources/views/errors/ runa/resources/views/errors/
COPY --chown=runa:runa src/runa/resources/css/light-palette.css runa/resources/css/light-palette.css
COPY --chown=runa:runa src/runa/platform.json runa/platform.json

# A short-lived host management process, never a production application service.
FROM production-runtime AS management
COPY --chown=runa:runa --from=php-dependencies /tmp/runa-build/src/vendor/ /opt/runa/vendor/
COPY --chown=runa:runa src/runa/management/autoload.php src/runa/management/bootstrap.php src/runa/management/commands.php src/runa/management/ToolingCommand.php src/runa/management/NamedToolCommand.php src/runa/management/operations.json src/runa/management/HostBridge.php /opt/runa/runa/management/
COPY --chown=runa:runa src/artisan /opt/runa/artisan
WORKDIR /opt/runa
HEALTHCHECK NONE
ENTRYPOINT ["php", "/opt/runa/artisan"]
CMD ["--management", "list"]

FROM management AS development-management
COPY --chown=runa:runa src/runa/management/ /opt/runa/runa/management/

FROM node AS frontend-build
WORKDIR /build/src
RUN npm install --global corepack@0.36.0 && corepack enable && corepack install --global pnpm@12.14.0
COPY src/package.json src/pnpm-lock.yaml src/pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile
COPY src/runa/resources/ runa/resources/
COPY src/runa/frontend/ runa/frontend/
COPY src/runa/modules/ runa/modules/
COPY src/app/resources/ app/resources/
COPY src/app/modules/ app/modules/
COPY src/app/application.json app/application.json
COPY src/runa/platform.json runa/platform.json
COPY src/vite.config.mjs src/vite.errors.config.mjs src/tsconfig.json ./
RUN pnpm run build

FROM php-dependencies AS error-pages-build
COPY --chown=runa:runa --from=frontend-build /build/.runtime/frontend/errors/templates/ /tmp/runa-build/.runtime/frontend/errors/templates/
COPY --chown=runa:runa --from=frontend-build /build/src/public/runa-error/ public/runa-error/
RUN php runa/resources/errors/build.php

FROM production-runtime AS production
COPY --from=production-runtime /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
ENV APP_ENV=production APP_DEBUG=false KNOWLEDGE_ENABLED=false
COPY --chmod=755 docker/php/production-entrypoint /usr/local/bin/runa-production-entrypoint
ENTRYPOINT ["runa-production-entrypoint"]
CMD ["php-fpm", "-F"]
COPY --chown=runa:runa src/composer.json /workspace/src/composer.json
COPY --chown=runa:runa --from=php-dependencies /tmp/runa-build/src/vendor/ /workspace/src/vendor/
COPY --chown=runa:runa --from=php-dependencies /tmp/runa-build/src/app/ /workspace/src/app/
COPY --chown=runa:runa src/runa/Console/ /workspace/src/runa/Console/
COPY --chown=runa:runa src/runa/Contracts/ /workspace/src/runa/Contracts/
COPY --chown=runa:runa src/runa/Database/ /workspace/src/runa/Database/
COPY --chown=runa:runa src/runa/Foundation/ /workspace/src/runa/Foundation/
COPY --chown=runa:runa src/runa/Http/ /workspace/src/runa/Http/
COPY --chown=runa:runa src/runa/Providers/ /workspace/src/runa/Providers/
COPY --chown=runa:runa src/runa/Support/ /workspace/src/runa/Support/
COPY --chown=runa:runa src/runa/Configuration/ /workspace/src/runa/Configuration/
COPY --chown=runa:runa src/runa/Identity/ /workspace/src/runa/Identity/
COPY --chown=runa:runa src/runa/Integration/ /workspace/src/runa/Integration/
COPY --chown=runa:runa src/runa/Logging/ /workspace/src/runa/Logging/
COPY --chown=runa:runa src/runa/Mail/ /workspace/src/runa/Mail/
COPY --chown=runa:runa src/runa/ModuleFramework/ /workspace/src/runa/ModuleFramework/
COPY --chown=runa:runa src/runa/Runtime/ /workspace/src/runa/Runtime/
COPY --chown=runa:runa src/runa/modules/ /workspace/src/runa/modules/
COPY --chown=runa:runa src/bootstrap/ /workspace/src/bootstrap/
COPY --chown=runa:runa src/runa/config/ /workspace/src/runa/config/
COPY --chown=runa:runa src/runa/database/ /workspace/src/runa/database/
COPY --chown=runa:runa src/runa/resources/css/light-palette.css /workspace/src/runa/resources/css/light-palette.css
COPY --chown=runa:runa src/runa/resources/views/ /workspace/src/runa/resources/views/
COPY --chown=runa:runa --from=error-pages-build /tmp/runa-build/.runtime/frontend/errors/templates/ /workspace/.runtime/frontend/errors/templates/
COPY --chown=runa:runa src/runa/resources/errors/messages.json /workspace/src/runa/resources/errors/messages.json
COPY --chown=runa:runa --from=error-pages-build /tmp/runa-build/src/public/runa-error/ /workspace/src/public/runa-error/
COPY --chown=runa:runa src/runa/resources/images/runa-mail-logo.png /workspace/src/runa/resources/images/runa-mail-logo.png
COPY --chown=runa:runa src/runa/platform.json /workspace/src/runa/platform.json
COPY --chown=runa:runa src/runa/routes/ /workspace/src/runa/routes/
COPY --chown=runa:runa src/runa/lang/ /workspace/src/runa/lang/
COPY --chown=runa:runa src/public/index.php src/public/favicon.ico /workspace/src/public/
COPY --chown=runa:runa --from=frontend-build /build/src/public/build/ /workspace/src/public/build/
RUN mkdir -p src/storage/app/application/private src/storage/app/application/public src/storage/app/runa/private src/storage/app/runa/public src/storage/framework/cache/data \
    src/storage/framework/sessions src/storage/framework/views src/storage/framework/health src/storage/framework/maintenance src/storage/logs
COPY --chown=runa:runa artisan /workspace/artisan
COPY --chown=runa:runa src/artisan /workspace/src/artisan

FROM nginx:1.31.7-alpine-slim@sha256:ebd058041a732a2b2dc0b606e34572dd8e0034de6a4aa4d50d8c13a240ac6005 AS production-nginx
COPY docker/nginx/production.conf /etc/nginx/nginx.conf
COPY src/public/favicon.ico /workspace/src/public/favicon.ico
COPY docker/nginx/security-headers.conf /etc/nginx/runa-security-headers.conf
COPY --from=error-pages-build /tmp/runa-build/src/public/runa-error/ /workspace/src/public/runa-error/
COPY --from=frontend-build /build/src/public/build/ /workspace/src/public/build/
USER nginx
ENTRYPOINT ["nginx", "-g", "daemon off;"]
HEALTHCHECK --interval=10s --timeout=3s --retries=5 CMD wget -qO- http://127.0.0.1:8080/api/ready || exit 1
