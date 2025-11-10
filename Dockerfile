# ==========================
# Étape 1 : Build des dépendances PHP avec Composer
# ==========================
FROM php:8.3-fpm-alpine AS composer-build

# Installer dépendances système et extensions PHP nécessaires
RUN apk add --no-cache \
        curl \
        composer \
        openssl-dev \
        autoconf \
        gcc \
        g++ \
        make \
        libtool \
        libzip-dev \
        oniguruma-dev \
        libxml2-dev \
        gmp-dev \
        zlib-dev \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install pdo pdo_mysql bcmath zip opcache \
    && apk del gcc g++ make autoconf libtool

WORKDIR /app

# Copier uniquement composer.json et composer.lock
COPY composer.json composer.lock /app/

# Installer dépendances sans scripts artisan
RUN composer install --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# Copier le reste du code source
COPY . .

# Installer Swagger
RUN composer require "zircote/swagger-php:^4.0" --no-scripts --no-interaction --prefer-dist

# ==========================
# Étape 2 : Image finale avec PHP-FPM + Nginx
# ==========================
FROM php:8.3-fpm-alpine

# Installer dépendances système
RUN apk add --no-cache \
        bash \
        nginx \
        freetype-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        libpng-dev \
        pkgconf \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd pcntl \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Créer utilisateur non-root
RUN addgroup -g 1000 laravel \
    && adduser -G laravel -g laravel -s /bin/sh -D laravel

WORKDIR /var/www/html

# Copier le code depuis l’étape build
COPY --from=composer-build /app /var/www/html

# Config Nginx
COPY ./nginx.conf /etc/nginx/nginx.conf

# Permissions
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

USER laravel

EXPOSE 80

CMD ["sh", "-c", "php artisan migrate --force && php artisan passport:install --force && php-fpm & nginx -g 'daemon off;'"]
