FROM php:8.5-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl libzip-dev libicu-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath intl zip gd exif pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Run PHP-FPM and all commands as the host user so no root-owned files appear on the host.
RUN (getent group ${GID} >/dev/null || groupadd -g ${GID} app) \
    && useradd -m -u ${UID} -g ${GID} -s /bin/bash app

WORKDIR /var/www/api
USER app
