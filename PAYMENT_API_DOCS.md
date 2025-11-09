# API de Paiement Marchand - Documentation

## Vue d'ensemble

Cette API permet d'effectuer des paiements vers des marchands en utilisant leurs codes marchands. Elle complète le système de transfert P2P.

## Authentification

Toutes les routes de paiement nécessitent une authentification via Laravel Passport :

```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

## Endpoints

### 1. Effectuer un paiement marchand

**POST** `/api/payment`

Effectue un paiement de l'utilisateur authentifié vers un marchand.

#### Request Body

```json
{
    "code_marchand": "BOUT001",
    "montant": 2500,
    "description": "Achat de produits alimentaires",
    "reference_externe": "CMD-2025-001"
}
```

#### Paramètres

-   `code_marchand` (string, requis) : Code du marchand destinataire
-   `montant` (numeric, requis) : Montant à payer (min: 100, max: 2,000,000)
-   `description` (string, optionnel) : Description du paiement (max: 255 caractères)
-   `reference_externe` (string, optionnel) : Référence externe (max: 100 caractères)

#### Réponse de succès (200)

```json
{
    "success": true,
    "message": "Paiement effectué avec succès",
    "data": {
        "success": true,
        "reference": "PAY_550e8400-e29b-41d4-a716-446655440000",
        "montant": 2500,
        "payeur": {
            "telephone": "771234567",
            "nom_complet": "Jean Dupont",
            "nouveau_solde": 47500
        },
        "marchand": {
            "code": "BOUT001",
            "nom": "Boutique Fatou",
            "nouveau_solde": 2500
        },
        "date_paiement": "2025-11-09T02:15:00.000Z",
        "statut": "completed",
        "metadata": {
            "description": "Achat de produits alimentaires",
            "reference_externe": "CMD-2025-001"
        }
    }
}
```

#### Erreurs possibles

-   **400** - Solde insuffisant, code marchand invalide
-   **401** - Non authentifié
-   **422** - Erreurs de validation

### 2. Vérifier un code marchand

**POST** `/api/payment/check-merchant`

Vérifie si un code marchand existe et est actif.

#### Request Body

```json
{
    "code_marchand": "BOUT001"
}
```

#### Réponse de succès (200)

```json
{
    "success": true,
    "exists": true,
    "message": "Marchand trouvé",
    "merchant": {
        "code": "BOUT001",
        "name": "Boutique Fatou",
        "telephone": "+221775551234",
        "email": "fatou@boutique.sn",
        "status": "active"
    }
}
```

### 3. Obtenir les informations d'un marchand

**POST** `/api/payment/merchant-info`

Récupère les informations publiques d'un marchand.

#### Request Body

```json
{
    "code_marchand": "BOUT001"
}
```

#### Réponse de succès (200)

```json
{
    "success": true,
    "merchant": {
        "code": "BOUT001",
        "name": "Boutique Fatou",
        "telephone": "+221775551234",
        "email": "fatou@boutique.sn",
        "status": "active"
    }
}
```

### 4. Historique des paiements

**GET** `/api/payment/history?page=1&limit=10`

Récupère l'historique des paiements de l'utilisateur authentifié.

#### Paramètres de requête

-   `page` (int, optionnel) : Numéro de page (défaut: 1)
-   `limit` (int, optionnel) : Nombre d'éléments par page (défaut: 10)

#### Réponse de succès (200)

```json
{
    "success": true,
    "message": "Historique des paiements récupéré avec succès",
    "data": [
        {
            "id": "pay_123",
            "type": "payment",
            "amount": -2500,
            "status": "success",
            "reference": "DEB_550e8400-e29b-41d4-a716-446655440000",
            "created_at": "2025-11-09T02:15:00.000Z",
            "meta": {
                "user_phone": "771234567",
                "merchant_code": "BOUT001",
                "merchant_name": "Boutique Fatou",
                "description": "Achat de produits alimentaires"
            }
        }
    ]
}
```

## Codes marchands de test

Après exécution du `MerchantTestSeeder` :

| Code       | Nom                        | Statut  | Solde Initial |
| ---------- | -------------------------- | ------- | ------------- |
| `BOUT001`  | Boutique Fatou             | Actif   | 0 FCFA        |
| `PHAR001`  | Pharmacie Al Azhar         | Actif   | 15,000 FCFA   |
| `REST001`  | Restaurant Teranga         | Actif   | 45,000 FCFA   |
| `STAT001`  | Station Essence Total      | Actif   | 120,000 FCFA  |
| `SALON001` | Salon de Coiffure Elegance | Inactif | 0 FCFA        |

## Règles métier

1. **Solde suffisant** : L'utilisateur doit avoir un solde suffisant
2. **Marchand actif** : Seuls les marchands avec le statut 'active' peuvent recevoir des paiements
3. **Montants** : Minimum 100 FCFA, maximum 2,000,000 FCFA
4. **Atomicité** : Les paiements sont transactionnels
5. **Traçabilité** : Chaque paiement génère deux transactions liées

## Types de transactions

-   `payment` : Transaction de paiement (utilisée pour débit et crédit)
-   Métadonnées distinguent `debit` (utilisateur) vs `credit` (marchand)

## Exemples d'utilisation

### Effectuer un paiement

```bash
curl -X POST http://localhost:8000/api/payment \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "code_marchand": "BOUT001",
    "montant": 2500,
    "description": "Achat de produits"
  }'
```

### Vérifier un marchand

```bash
curl -X POST http://localhost:8000/api/payment/check-merchant \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "code_marchand": "BOUT001"
  }'
```

### Historique des paiements

```bash
curl -X GET http://localhost:8000/api/payment/history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Intégration avec le système de transfert

-   Les deux systèmes partagent la même base de données `transactions`
-   Les wallets peuvent recevoir des transferts P2P et des paiements marchands
-   L'historique global peut inclure les deux types d'opérations
