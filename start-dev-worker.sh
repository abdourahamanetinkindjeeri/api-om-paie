#!/bin/bash

# Script simple pour démarrer le worker de queue en développement
# Usage: ./start-dev-worker.sh

echo "🔧 Démarrage du worker de queue (Mode développement)..."

# Nettoyer les configurations
php artisan config:clear
php artisan cache:clear

# Appliquer les migrations de queue si nécessaire
php artisan migrate --no-interaction 2>/dev/null || echo "⚠️ Migrations ignorées"

echo "🚀 Démarrage du worker..."
echo "💡 Pour arrêter : Ctrl+C"
echo ""

# Démarrer le worker en mode développement (plus verbeux)
php artisan queue:work \
    --queue=notifications,emails,default \
    --sleep=1 \
    --tries=2 \
    --max-time=300 \
    --memory=256 \
    --timeout=60 \
    --verbose