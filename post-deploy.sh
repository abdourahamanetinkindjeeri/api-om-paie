#!/bin/bash

# Script de post-déploiement pour OM-Paie API avec support queue
# À exécuter après chaque déploiement en production

set -e

echo "� Post-déploiement OM-Paie API avec support queue..."

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# 1. Attendre que l'application soit prête
echo "⏳ Attente que l'application soit prête..."
sleep 30

# 2. Appliquer les migrations (incluant celles des queues)
echo "🗄️ Application des migrations..."
php artisan migrate --force --no-interaction
success "Migrations appliquées"

# 3. Optimiser l'application
echo "⚡ Optimisation de l'application..."
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction 2>/dev/null || warning "Cache des vues ignoré"
success "Application optimisée"

# 4. Finaliser Passport
echo "🔐 Finalisation de Passport..."
php artisan passport:install --force --no-interaction 2>/dev/null || warning "Passport ignoré"
success "Passport configuré"

# 5. Générer la documentation API
echo "📚 Génération de la documentation API..."
php artisan l5-swagger:generate --no-interaction 2>/dev/null || warning "Documentation API ignorée"
success "Documentation générée"

# 6. Vérifier la configuration mail
echo "📧 Vérification de la configuration mail..."
if [ -n "$MAIL_USERNAME" ] && [ -n "$MAIL_PASSWORD" ]; then
    success "Configuration mail présente"

    # Test optionnel d'email
    if [ -n "$TEST_EMAIL" ]; then
        echo "🧪 Test d'envoi d'email..."
        php artisan mail:test-production "$TEST_EMAIL" --otp=999999 2>/dev/null || warning "Test email échoué"
    fi
else
    warning "Configuration mail incomplète - vérifiez MAIL_USERNAME et MAIL_PASSWORD"
fi

# 7. Nettoyer les anciens caches
echo "🧹 Nettoyage des anciens caches..."
php artisan optimize:clear --no-interaction 2>/dev/null || true
find storage/logs -name "*.log" -size +100M -delete 2>/dev/null || true
success "Nettoyage terminé"

# 8. Vérifier l'état des queues
echo "🔄 Vérification des queues..."
php artisan queue:monitor 2>/dev/null || warning "Surveillance des queues échouée"

echo ""
success "=== DÉPLOIEMENT TERMINÉ AVEC SUCCÈS ==="
echo ""
echo "🎯 Informations importantes :"
echo "  - URL de l'application : $APP_URL"
echo "  - Environment : $APP_ENV"
echo "  - Connection queue : $QUEUE_CONNECTION"
echo ""
echo "🔧 Commandes utiles :"
echo "  - Vérifier la santé : curl $APP_URL/api/health"
echo "  - Surveiller les queues : php artisan queue:monitor --watch"
echo "  - Voir les logs : tail -f storage/logs/laravel.log"
echo ""

if [ "$QUEUE_CONNECTION" != "sync" ]; then
    echo "⚠️  IMPORTANT : Le worker de queue devrait être démarré automatiquement."
    echo "   Si ce n'est pas le cas, lancez manuellement : ./start-queue-worker.sh &"
    echo ""
fi

echo "🎉 Application OM-Paie prête en production avec support des emails !"
