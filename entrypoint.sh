#!/bin/sh

echo "🚀 Démarrage de l'application OM-Paie API..."

# Créer le répertoire des vues compilées
mkdir -p /tmp/views
chmod 777 /tmp/views 2>/dev/null || true

# Permissions
chmod -R 775 storage bootstrap/cache 2>/dev/null || true


############################################
# 1️⃣ Copier les clés OAuth depuis Render (si dispo)
############################################

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


############################################
# 2️⃣ Générer automatiquement les clés Passport si absentes
############################################

if [ ! -f storage/oauth-private.key ] || [ ! -f storage/oauth-public.key ]; then
    echo "🔐 Aucune clé trouvée → Génération automatique de Passport..."

    # Supprimer d’anciennes clés au cas où
    rm -f storage/oauth-private.key storage/oauth-public.key 2>/dev/null || true

    php artisan passport:keys --force --no-interaction

    # Remettre les bonnes permissions
    chmod 600 storage/oauth-private.key
    chmod 644 storage/oauth-public.key

    echo "✅ Clés Passport générées avec succès."
else
    echo "🔑 Clés OAuth trouvées, aucune génération nécessaire."
fi


############################################
# 3️⃣ Générer la clé APP si manquante
############################################

if [ -z "$APP_KEY" ]; then
    echo "🔑 APP_KEY absente → génération..."
    php artisan key:generate --force --no-interaction
fi


############################################
# 4️⃣ Cache de configuration
############################################

echo "⚡ Optimisation de la configuration..."
php artisan config:cache --no-interaction


############################################
# 5️⃣ Tâches en arrière-plan
############################################

(
    sleep 10
    echo "📚 Génération différée de la documentation..."
    php artisan l5-swagger:generate --no-interaction 2>/dev/null || true
    php artisan route:cache --no-interaction 2>/dev/null || true

    if [ "$QUEUE_CONNECTION" != "sync" ]; then
        echo "🔄 Démarrage du worker de queue..."
        sleep 5
        ./start-queue-worker.sh 2>&1 | while IFS= read -r line; do
            echo "[QUEUE] $line"
        done &
    else
        echo "⏭️ Mode queue sync → worker non démarré"
    fi
) &


############################################
# 6️⃣ Démarrer l'application
############################################

echo "🚀 Lancement final..."
exec "$@"
