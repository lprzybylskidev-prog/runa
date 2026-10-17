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

FROM runtime AS development
USER root
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && apt-get update \
    && apt-get install -y --no-install-recommends git openssh-client curl unzip zip python3 procps ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && npm install --global corepack@0.36.0 \
    && corepack enable \
    && COREPACK_HOME=/opt/corepack corepack install --global pnpm@12.14.0 \
    && chown -R runa:runa /opt/corepack
ENV COREPACK_HOME=/opt/corepack
ENV COMPOSER_HOME=/home/runa/.config/composer
USER runa
RUN pnpm --version
