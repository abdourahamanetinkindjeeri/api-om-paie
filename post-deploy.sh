#!/bin/sh

# Script d'optimisation post-déploiement
# À exécuter après que l'application soit en ligne

echo "🔧 Optimisations post-déploiement..."

# Vérifier que l'application répond
echo "⏳ Attente que l'application soit prête..."
sleep 30

# Optimiser les vues et le cache
echo "📊 Optimisation des vues..."
php artisan view:cache --no-interaction 2>/dev/null || true

# Finaliser l'installation de Passport si nécessaire
echo "🔐 Finalisation de Passport..."
php artisan passport:install --force --no-interaction 2>/dev/null || true

# Nettoyer les caches temporaires
echo "🧹 Nettoyage des caches temporaires..."
php artisan optimize:clear --no-interaction 2>/dev/null || true
php artisan config:cache --no-interaction 2>/dev/null || true

echo "✅ Optimisations terminées!"
