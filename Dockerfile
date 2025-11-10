# ==========================
# Étape 1 : Build des dépendances PHP avec Composer et MongoDB
# ==========================
FROM php:8.3-fpm-alpine AS build

# Installer dépendances système et outils de compilation
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
        libsodium-dev

# Installer extensions PHP nécessaires
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    bcmath \
    zip \
    opcache \
    dom \
    tokenizer \
    session \
    fileinfo \
    sodium


# Compiler et activer MongoDB
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

WORKDIR /app

# Copier uniquement composer.json et composer.lock
COPY composer.json composer.lock /app/

# Installer dépendances Laravel
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
        libsodium-dev

# Configurer et installer GD et autres extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install gd pcntl pdo pdo_mysql bcmath zip opcache dom tokenizer session fileinfo sodium

# Copier l’extension MongoDB compilée depuis le stage build
COPY --from=build /usr/local/lib/php/extensions/no-debug-non-zts-20230831/mongodb.so /usr/local/lib/php/extensions/no-debug-non-zts-20230831/
RUN echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini

# Créer utilisateur non-root
RUN addgroup -g 1000 laravel \
    && adduser -G laravel -g laravel -s /bin/sh -D laravel

WORKDIR /var/www/html

# Copier le code depuis l’étape build
COPY --from=build /app /var/www/html

# Config Nginx
COPY ./nginx.conf /etc/nginx/nginx.conf

# Permissions
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

USER laravel

# Exposer le port 8000 pour Render
EXPOSE 8000

# Lancer migrations + Passport + services
CMD ["sh", "-c", "php artisan migrate --force && php artisan passport:install --force && php-fpm & nginx -g 'daemon off;'"]
