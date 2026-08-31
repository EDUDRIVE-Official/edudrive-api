# Etapa 1: build de assets (Tailwind/Alpine vía Vite) para el panel web.
FROM node:20-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public

RUN npm run build

# Etapa 2: imagen de nginx para produccion (compose.prod.yaml la construye
# con --target nginx). Sirve los assets ya compilados directamente, sin
# depender de un volumen compartido con el contenedor de la app. Solo se
# construye cuando se pide este target explicitamente; el "docker build"
# simple que usa CI (sin --target) sigue produciendo la imagen de la app
# de la etapa siguiente, sin costo adicional.
FROM nginx:alpine AS nginx

COPY --from=assets /app/public /var/www/html/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Etapa 3: imagen final de la aplicacion (php-fpm).
#
# Es el mismo Dockerfile usado en desarrollo: en local, el bind mount del
# compose (./:/var/www/html) sustituye por completo el codigo/assets/vendor
# horneados aqui, asi que este COPY/composer install/npm build no afecta el
# flujo de desarrollo. Sirve para que la imagen publicada por CI
# (ghcr.io/edudrive-official/edudrive-api) sea autosuficiente y ejecutable
# tal cual en un despliegue real (ver docs/operaciones/despliegue-vps.md),
# sin mantener un Dockerfile de produccion separado (decision de ENG-086).
FROM php:8.4-fpm AS app

ARG USER_ID=1000
ARG GROUP_ID=1000

ENV DEBIAN_FRONTEND=noninteractive

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        curl \
        unzip \
        libpq-dev \
        libzip-dev \
        libicu-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        postgresql-client \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupmod -g ${GROUP_ID} www-data \
    && usermod -u ${USER_ID} -g ${GROUP_ID} www-data

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer install --no-dev --no-interaction --optimize-autoloader --no-ansi --no-scripts \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data

CMD ["php-fpm"]
