# OM-Paie API

API REST Laravel pour le système de paiement mobile OM-Paie, permettant les transferts d'argent entre utilisateurs et les paiements vers les marchands.

## 🚀 Fonctionnalités

- **Authentification sécurisée** : Inscription/connexion avec 2FA (OTP SMS)
- **Transferts P2P** : Transferts d'argent entre utilisateurs avec protection ACID
- **Paiements marchands** : Paiement vers les commerçants via codes marchands
- **QR Codes** : Génération et scan de QR codes pour faciliter les transactions
- **Historique complet** : Suivi des transactions avec pagination
- **Notifications SMS** : Alertes en temps réel via Twilio
- **Architecture robuste** : Laravel + MongoDB avec files d'attente

## 🛠️ Technologies

- **Framework** : Laravel 10.x
- **Base de données** : MongoDB
- **Authentification** : Laravel Passport (OAuth2)
- **Files d'attente** : Laravel Queues
- **Notifications** : Twilio SMS
- **Documentation** : Swagger/OpenAPI
- **QR Codes** : Endroid QR Code
- **Conteneurisation** : Docker

## 📋 Prérequis

- PHP 8.1+
- MongoDB 4.4+
- Composer
- Docker (optionnel)

## 🚀 Installation

### Avec Docker (Recommandé)

```bash
# Cloner le repository
git clone <repository-url>
cd api-om-paie

# Copier le fichier d'environnement
cp .env.example .env

# Construire et démarrer les conteneurs
docker-compose up -d

# Installer les dépendances
docker-compose exec app composer install

# Générer la clé d'application
docker-compose exec app php artisan key:generate

# Exécuter les migrations
docker-compose exec app php artisan migrate

# Démarrer les workers de file d'attente
docker-compose exec app php artisan queue:work
```

### Installation locale

```bash
# Cloner le repository
git clone <repository-url>
cd api-om-paie

# Installer les dépendances PHP
composer install

# Copier le fichier d'environnement
cp .env.example .env

# Configurer l'environnement (.env)
# - DB_CONNECTION=mongodb
# - MONGODB_URI=mongodb://localhost:27017
# - TWILIO_SID=your_twilio_sid
# - TWILIO_TOKEN=your_twilio_token

# Générer la clé d'application
php artisan key:generate

# Installer Passport
php artisan passport:install

# Exécuter les migrations
php artisan migrate

# Démarrer le serveur
php artisan serve
```

## 📖 API Endpoints

### Authentification
```
POST /api/auth/register      # Inscription (init)
POST /api/auth/confirmation  # Confirmation OTP
POST /api/auth/resend        # Renvoi OTP
POST /api/auth/login         # Connexion (étape 1)
POST /api/auth/login/confirm # Confirmation connexion
POST /api/auth/refresh       # Rafraîchir token
POST /api/auth/logout        # Déconnexion
GET  /api/auth/me           # Profil utilisateur
```

### Transferts
```
POST /api/transfer/          # Effectuer un transfert
POST /api/transfer/check-number # Vérifier numéro
GET  /api/transfer/balance   # Consulter solde
GET  /api/transfer/history   # Historique transferts
```

### Paiements
```
POST /api/payment/           # Payer un marchand
POST /api/payment/check-merchant # Vérifier code marchand
POST /api/payment/merchant-info # Infos marchand
GET  /api/payment/history    # Historique paiements
```

### Historique
```
GET /api/history/            # Historique complet
GET /api/history/transfers   # Transferts seulement
GET /api/history/payments    # Paiements seulement
GET /api/history/stats       # Statistiques
```

### QR Codes
```
GET  /api/user/qrcode        # Générer QR code
GET  /api/user/qrcode/data   # Données QR code
POST /api/qrcode/scan        # Scanner QR code
```

### Monitoring
```
GET /api/health              # Health check
GET /api/status              # Status simple
```

## 🔧 Configuration

### Variables d'environnement (.env)

```env
# Application
APP_NAME=OM-Paie
APP_ENV=production
APP_KEY=
APP_DEBUG=false

# Base de données MongoDB
DB_CONNECTION=mongodb
MONGODB_URI=mongodb://localhost:27017/ompaie
DB_DATABASE=ompaie

# Twilio (SMS)
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM=+1234567890

# Queue
QUEUE_CONNECTION=database
```

## 🧪 Tests

```bash
# Exécuter tous les tests
php artisan test

# Tests avec couverture
php artisan test --coverage
```

## 📚 Documentation API

La documentation Swagger est disponible sur `/api/documentation` une fois l'application démarrée.

## 🔄 Files d'attente (Queues)

```bash
# Démarrer un worker
php artisan queue:work

# Démarrer avec supervision
php artisan queue:work --tries=3 --timeout=90

# Cron pour les jobs échoués
* * * * * cd /path-to-your-project && php artisan queue:work --sleep=3 --tries=3 >> /dev/null 2>&1
```

## 🐳 Déploiement Docker

### Build de l'image
```bash
docker build -t om-paie-api .
```

### Déploiement sur Render
Le projet est configuré pour Render avec :
- `render.yaml` pour la configuration
- Health checks automatiques
- Variables d'environnement

## 🔒 Sécurité

- Authentification OAuth2 avec Laravel Passport
- 2FA obligatoire pour toutes les connexions
- Rate limiting sur toutes les routes API
- Validation stricte des données d'entrée
- Sanitisation des sorties
- Logs détaillés des opérations sensibles

## 📊 Monitoring

- Health checks intégrés
- Logs structurés
- Métriques de performance
- Alertes sur les échecs de queue

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📝 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 📞 Support

Pour le support technique, contactez l'équipe de développement.

---

**OM-Paie** - Solution de paiement mobile sécurisée et fiable.
