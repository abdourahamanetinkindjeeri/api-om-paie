#!/bin/bash

# Script de démarrage du worker de queue pour la production
# Ce script doit être exécuté en arrière-plan dans votre environnement de production

set -e

echo "🚀 Démarrage du worker de queue OM-Paie..."

# Fonction pour démarrer le worker
start_worker() {
    echo "🔄 Lancement du worker de queue..."

    # Vérifier que Laravel est bien configuré (sans cache pour éviter les conflits)
    php artisan config:clear 2>/dev/null || true

    # Démarrer le worker avec une configuration optimisée pour la production
    php artisan queue:work \
        --queue=notifications,emails,default \
        --sleep=3 \
        --tries=3 \
        --max-time=3600 \
        --memory=512 \
        --timeout=300 \
        --rest=1 \
        --verbose
}

# Fonction pour surveiller et redémarrer le worker si nécessaire
supervise_worker() {
    local restart_count=0
    local max_restarts=10

    while true; do
        echo "🎯 Tentative $((restart_count + 1)) de démarrage du worker..."

        if start_worker; then
            echo "✅ Worker terminé normalement"
        else
            echo "⚠️ Worker terminé avec erreur (code: $?)"
        fi

        restart_count=$((restart_count + 1))

        if [ $restart_count -ge $max_restarts ]; then
            echo "❌ Nombre maximum de redémarrages atteint ($max_restarts)"
            echo "🛑 Arrêt de la supervision du worker"
            exit 1
        fi

        echo "⏳ Attente de 10 secondes avant redémarrage..."
        sleep 10
    done
}

# Vérifier que les migrations de queue sont appliquées
echo "📋 Vérification des migrations de queue..."
php artisan migrate --force --no-interaction 2>/dev/null || echo "⚠️ Migrations échouées ou non nécessaires"

# Démarrer la supervision
echo "🛡️ Démarrage de la supervision du worker de queue..."
supervise_worker
