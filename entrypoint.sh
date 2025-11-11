#!/bin/sh

echo "🚀 Démarrage de l'application OM-Paie API..."

# Créer le répertoire des vues compilées pour Render
mkdir -p /tmp/views
chmod 777 /tmp/views 2>/dev/null || true

# Vérifier les permissions de base
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Copier les clés OAuth depuis Render Secret Files si elles existent
if [ -f /etc/secrets/oauth-private.key ]; then
    echo "📋 Copie de la clé privée OAuth..."
    cp /etc/secrets/oauth-private.key storage/oauth-private.key
    chmod 600 storage/oauth-private.key
fi

if [ -f /etc/secrets/oauth-public.key ]; then
    echo "📋 Copie de la clé publique OAuth..."
    cp /etc/secrets/oauth-public.key storage/oauth-public.key
    chmod 644 storage/oauth-public.key
fi

# Générer la clé d'application si elle n'existe pas
if [ -z "$APP_KEY" ]; then
    echo "🔑 Génération de la clé d'application..."
    php artisan key:generate --force --no-interaction
fi

# Configuration minimale pour éviter les timeouts
echo "⚡ Configuration rapide..."
php artisan config:cache --no-interaction

# Créer les clés Passport si elles n'existent pas (en mode non-interactif)
if [ ! -f storage/oauth-private.key ]; then
    echo "🔐 Configuration de Laravel Passport..."
    php artisan passport:keys --force --no-interaction 2>/dev/null || true
fi

echo "✅ Application prête - démarrage du serveur..."

# Démarrer les tâches en arrière-plan après le serveur
(
    sleep 10
    echo "📚 Génération différée de la documentation..."
    php artisan l5-swagger:generate --no-interaction 2>/dev/null || true
    php artisan route:cache --no-interaction 2>/dev/null || true
    
    # Démarrer le worker de queue si nécessaire
    if [ "$QUEUE_CONNECTION" != "sync" ]; then
        echo "🔄 Démarrage du worker de queue..."
        sleep 5
        ./start-queue-worker.sh 2>&1 | while IFS= read -r line; do
            echo "[QUEUE] $line"
        done &
    else
        echo "⏭️ Queue en mode sync - worker non démarré"
    fi
) &

exec "$@"
