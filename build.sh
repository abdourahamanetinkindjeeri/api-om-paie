#!/bin/bash

# Build script pour Render.com
set -o errexit

echo "🚀 Démarrage du build sur Render..."

# Installer les dépendances Composer
echo "📦 Installation des dépendances PHP..."
composer install --no-dev --optimize-autoloader

# Créer les répertoires de cache
echo "📁 Création des répertoires..."
mkdir -p storage/logs
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/cache

# Configurer les permissions
echo "🔐 Configuration des permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Générer la clé d'application
echo "🔑 Génération de la clé d'application..."
php artisan key:generate --force

# Effacer et reconstruire le cache
echo "💾 Configuration du cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Générer la documentation Swagger
echo "📚 Génération de la documentation Swagger..."
php artisan l5-swagger:generate

echo "✅ Build terminé avec succès !"