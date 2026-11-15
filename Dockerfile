# syntax=docker/dockerfile:1
FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package.json ./
RUN npm install --no-audit --no-fund
COPY resources ./resources
COPY public ./public
COPY vite.config.ts tsconfig.json ./
RUN npm run build

FROM dunglas/frankenphp:1-php8.4-bookworm AS php-base
RUN install-php-extensions pdo_pgsql pdo_sqlite intl zip opcache pcntl redis

FROM php-base AS dependencies
WORKDIR /app
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY . /app
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --optimize-autoloader

FROM php-base AS runtime
WORKDIR /app
COPY --from=dependencies /app /app
COPY --from=frontend /app/public/build /app/public/build
COPY docker/Caddyfile /etc/caddy/Caddyfile
RUN mkdir -p /app/storage/app/private /app/storage/framework/cache/data /app/storage/framework/sessions /app/storage/framework/views /app/storage/logs /app/bootstrap/cache /data /config \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache /data /config
ENV SERVER_NAME=:8080
USER www-data
EXPOSE 8080
