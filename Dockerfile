# syntax=docker/dockerfile:1.7

FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

FROM php:8.3-apache-bookworm AS php-base

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libmagickwand-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libxml2-dev \
        libzip-dev \
        poppler-utils \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd intl mbstring opcache pdo_pgsql pgsql zip \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && a2enmod expires headers rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/99-sdaya.ini

FROM php-base AS application
WORKDIR /var/www/html

ARG INSTALL_DEV=false

COPY composer.json composer.lock* ./
RUN if [ "$INSTALL_DEV" = "true" ]; then \
        composer install --no-interaction --prefer-dist --no-scripts --no-autoloader; \
    else \
        composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader; \
    fi

COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN if [ "$INSTALL_DEV" = "true" ]; then \
        composer install --no-interaction --prefer-dist --optimize-autoloader; \
    else \
        composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
        && rm -rf tests phpunit.xml; \
    fi \
    && mkdir -p storage/app/private/documentos storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/sdaya-entrypoint
RUN chmod +x /usr/local/bin/sdaya-entrypoint

EXPOSE 80
ENTRYPOINT ["sdaya-entrypoint"]
CMD ["apache2-foreground"]
