#!/bin/sh

echo "🚀 Démarrage de l'application OM-Paie API..."

# Copier les clés OAuth depuis Render Secret Files si elles existent
if [ -f /etc/secrets/oauth-private.key ]; then
    echo "📋 Copie de la clé privée OAuth..."
    cp /etc/secrets/oauth-private.key storage/oauth-private.key
fi

if [ -f /etc/secrets/oauth-public.key ]; then
    echo "📋 Copie de la clé publique OAuth..."
    cp /etc/secrets/oauth-public.key storage/oauth-public.key
fi

# Générer la clé d'application si elle n'existe pas
if [ -z "$APP_KEY" ]; then
    echo "🔑 Génération de la clé d'application..."
    php artisan key:generate --force
fi

# Configuration du cache et optimisations
echo "⚡ Configuration du cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Générer la documentation Swagger
echo "📚 Génération de la documentation Swagger..."
php artisan l5-swagger:generate

# Créer les clés Passport si elles n'existent pas
if [ ! -f storage/oauth-private.key ]; then
    echo "🔐 Installation de Laravel Passport..."
    php artisan passport:install --force
fi

echo "✅ Application prête - démarrage du serveur..."
exec "$@"
