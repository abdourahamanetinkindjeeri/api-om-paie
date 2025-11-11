# ==========================
# Étape 1 : Build des dépendances PHP avec Composer et MongoDB
# ==========================
FROM php:8.3-fpm-alpine AS composer-build

# Mettre à jour les index des paquets et installer les dépendances système
RUN apk update && apk add --no-cache \
        bash \
        curl \
        freetype-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        libpng-dev \
        zlib-dev \
        oniguruma-dev \
        libxml2-dev \
        gmp-dev \
        libzip-dev \
        openssl-dev \
        libsodium-dev \
        autoconf \
        gcc \
        g++ \
        make \
        libtool \
        pkgconfig \
        linux-headers

# Installer les extensions PHP une par une pour éviter les conflits
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install gd
RUN docker-php-ext-install pcntl
RUN docker-php-ext-install zip
RUN docker-php-ext-install opcache
RUN docker-php-ext-install dom
RUN docker-php-ext-install session
RUN docker-php-ext-install fileinfo
RUN docker-php-ext-install sodium

# Installer MongoDB avec ses dépendances
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Installer Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copier uniquement composer.json et composer.lock pour profiter du cache Docker
COPY composer.json composer.lock /app/

# Installer les dépendances sans exécuter les scripts artisan
RUN composer install --no-scripts --optimize-autoloader --no-interaction --prefer-dist

# Copier le reste du code source
COPY . .

# ==========================
# Étape 2 : Image finale PHP-FPM optimisée pour OM-Paie API
# ==========================
FROM php:8.3-fpm-alpine

# Installer dépendances système et extensions pour MongoDB, GD (QR Code), et Swagger
RUN apk add --no-cache \
        bash \
        curl \
        freetype-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        libpng-dev \
        zlib-dev \
        oniguruma-dev \
        libxml2-dev \
        gmp-dev \
        libzip-dev \
        openssl-dev \
        libsodium-dev \
        autoconf \
        gcc \
        g++ \
        make \
        libtool \
        pkgconfig \
        linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install \
        bcmath \
        gd \
        pcntl \
        zip \
        opcache \
        dom \
        session \
        fileinfo \
        sodium \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apk del gcc g++ make autoconf libtool pkgconfig

# Configuration d'OPcache pour optimiser les performances
RUN echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Configuration PHP pour MongoDB et performance
RUN echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize=32M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=32M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/custom.ini

# Créer un utilisateur non-root
RUN addgroup -g 1000 laravel \
    && adduser -G laravel -g laravel -s /bin/sh -D laravel

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le code depuis l'étape build avec les bonnes permissions
COPY --from=composer-build --chown=laravel:laravel /app /var/www/html

# Créer les répertoires nécessaires rapidement
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    storage/logs \
    storage/api-docs \
    bootstrap/cache \
    public/storage \
    && chmod -R 775 storage bootstrap/cache \
    && chmod -R 755 public

# Copier le script d'entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Variables d'environnement pour MongoDB et l'application
ENV DB_CONNECTION=mongodb
ENV MONGODB_URI=""
ENV APP_ENV=production
ENV APP_DEBUG=false

# Utilisateur non-root par défaut
USER laravel

# Exposer le port 8000
EXPOSE 8000

# Health check optimisé pour Render
HEALTHCHECK --interval=60s --timeout=30s --start-period=180s --retries=3 \
    CMD curl -f http://localhost:8000/api/health || exit 1

# Entrypoint
ENTRYPOINT ["/entrypoint.sh"]

# Commande par défaut optimisée pour Render
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000", "--no-reload"]
