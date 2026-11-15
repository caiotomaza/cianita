# syntax=docker/dockerfile:1
FROM node:22-bookworm-slim@sha256:e9f565d32859f0cbfb52125eafe8be39390f24dac302dc3cdf566e8ba3ab21ee AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY public ./public
COPY vite.config.ts tsconfig.json ./
RUN npm run build

FROM dunglas/frankenphp:1-php8.4-bookworm@sha256:bd101be4e0f384dba7da85bfc8d13911998b4fee4ed42ec7959dc0e67619c161 AS php-base
RUN install-php-extensions pdo_pgsql pdo_sqlite intl zip opcache pcntl redis mbstring xml

FROM php-base AS dependencies
WORKDIR /app
COPY --from=composer:2@sha256:ca11f643d7791ebdd90a34fc20caf11380d434007ae539a9ea75cd9212e13dca /usr/bin/composer /usr/local/bin/composer
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
