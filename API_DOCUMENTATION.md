# OM-Paie API Documentation

## 🚀 API de Paiement Mobile - Version 2.0.0

L'API OM-Paie permet de gérer les transferts d'argent entre utilisateurs et les paiements vers les marchands. Elle est construite avec Laravel et utilise MongoDB comme base de données.

## 📚 Documentation Interactive

La documentation Swagger interactive est disponible à l'adresse :

```
http://localhost:8001/api/documentation
```

## 🔐 Authentification

L'API utilise Laravel Passport pour l'authentification OAuth2. Pour accéder aux endpoints protégés, vous devez :

1. **S'inscrire** avec `/api/auth/register`
2. **Se connecter** avec `/api/auth/login` pour obtenir un token
3. **Utiliser le token** dans l'en-tête `Authorization: Bearer {token}`

### Exemple d'inscription :

```bash
curl -X POST http://localhost:8001/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Diop",
    "prenom": "Mamadou",
    "telephone": "+221771234567",
    "code": "1234",
    "code_confirmation": "1234",
    "email": "mamadou@example.com"
  }'
```

### Exemple de connexion :

```bash
curl -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "telephone": "+221771234567",
    "code": "1234"
  }'
```

## 🏗️ Architecture

### Base de données

-   **MongoDB** - Base de données NoSQL serverless (Railway)
-   **Collections** : users, merchants, wallets, transactions, transaction_logs

### Modèles principaux

-   **User** : Utilisateurs de l'application
-   **Merchant** : Marchands acceptant les paiements
-   **Wallet** : Portefeuilles des utilisateurs et marchands
-   **Transaction** : Transactions (transferts et paiements)
-   **TransactionLog** : Logs des transactions

## 📍 Endpoints Principaux

### Authentication

-   `POST /api/auth/register` - Inscription
-   `POST /api/auth/login` - Connexion
-   `POST /api/auth/logout` - Déconnexion
-   `GET /api/auth/me` - Profil utilisateur
-   `POST /api/auth/unlock-account` - Débloquer un compte

### Transferts

-   `POST /api/transfer` - Effectuer un transfert
-   `POST /api/transfer/check-number` - Vérifier un numéro
-   `GET /api/transfer/balance` - Obtenir le solde
-   `GET /api/transfer/history` - Historique des transferts

### Paiements Marchands

-   `POST /api/payment` - Payer un marchand
-   `POST /api/payment/check-merchant` - Vérifier un code marchand
-   `POST /api/payment/merchant-info` - Infos marchand
-   `GET /api/payment/history` - Historique des paiements
-   `POST /api/payment/merchant-history` - Historique marchand

## 🧪 Tests

### Données de test

L'application inclut des seeders pour créer des données de test :

```bash
php artisan db:seed
```

Cela créera :

-   ✅ Utilisateurs de test
-   ✅ Marchands de test
-   ✅ Portefeuilles avec soldes
-   ✅ Transactions d'exemple

### Utilisateurs de test

Après le seeding, vous pouvez utiliser n'importe quel utilisateur créé avec le code PIN `1234`.

## 🛠️ Développement

### Prérequis

-   PHP 8.1+
-   Extension MongoDB (`pecl install mongodb`)
-   Composer
-   Laravel 10.x

### Installation

```bash
# Cloner le projet
git clone <repository>

# Installer les dépendances
composer install

# Configuration
cp .env.example .env
php artisan key:generate

# Migration et seeding (MongoDB)
php artisan db:seed

# Générer les clés Passport
php artisan passport:install

# Générer la documentation Swagger
php artisan l5-swagger:generate

# Démarrer le serveur
php artisan serve
```

### Configuration MongoDB

Le projet utilise Railway MongoDB. Configuration dans `.env` :

```env
DB_CONNECTION=mongodb
MONGODB_URI=${MONGO_PUBLIC_URL}
MONGO_PUBLIC_URL=mongodb://mongo:TyUsJXCHYKEMcEzELakgMgjBMdjEFqfv@gondola.proxy.rlwy.net:14762/railway
```

## 📝 Format des Réponses

### Succès

```json
{
    "success": true,
    "message": "Opération réussie",
    "data": {
        // Données de réponse
    }
}
```

### Erreur

```json
{
    "success": false,
    "message": "Message d'erreur",
    "error": "error_code"
}
```

### Validation

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "field_name": ["Le champ est requis."]
    }
}
```

## 🌍 Environnements

-   **Développement** : `http://localhost:8001`
-   **Documentation Swagger** : `/api/documentation`
-   **Fichier OpenAPI** : `/api/api-docs.json`

## 📞 Support

Pour toute question ou problème :

-   **Email** : support@om-paie.sn
-   **Documentation** : Consultez Swagger UI pour les détails complets

---

**Version** : 2.0.0  
**Base de données** : MongoDB (Serverless)  
**Framework** : Laravel 10.x  
**Authentification** : Laravel Passport (OAuth2)
