# API de Transfert - Documentation

## Authentification

Toutes les routes de transfert nécessitent une authentification via Laravel Passport. Incluez le token d'accès dans l'en-tête Authorization :

```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

## Endpoints

### 1. Effectuer un transfert

**POST** `/api/transfer`

Effectue un transfert d'argent entre l'utilisateur authentifié et un destinataire.

#### Request Body

```json
{
    "numero": "221771234568",
    "montant": 1000
}
```

#### Paramètres

-   `numero` (string, required) : Numéro du destinataire (doit exister dans le système)
-   `montant` (numeric, required) : Montant à transférer (min: 1, max: 1,000,000)

#### Réponse de succès (200)

```json
{
    "success": true,
    "message": "Transfert effectué avec succès",
    "data": {
        "success": true,
        "reference": "TRF_9d1c4e82-1234-5678-9012-123456789abc",
        "montant": 1000,
        "expediteur": {
            "numero": "221771234567",
            "nom_complet": "Jean Dupont",
            "nouveau_solde": 9000
        },
        "destinataire": {
            "numero": "221771234568",
            "nom_complet": "Marie Martin",
            "nouveau_solde": 6000
        },
        "date_transfert": "2025-11-09T01:15:00.000000Z",
        "statut": "completed"
    }
}
```

#### Réponses d'erreur

-   **400** : Solde insuffisant, utilisateur non trouvé, etc.
-   **422** : Erreurs de validation
-   **401** : Non authentifié

### 2. Vérifier l'existence d'un numéro

**POST** `/api/transfer/check-number`

Vérifie si un numéro existe dans le système.

#### Request Body

```json
{
    "numero": "221771234568"
}
```

#### Réponse de succès (200)

```json
{
    "success": true,
    "exists": true,
    "message": "Numéro trouvé"
}
```

### 3. Obtenir le solde

**GET** `/api/transfer/balance`

Récupère le solde de l'utilisateur authentifié.

#### Réponse de succès (200)

```json
{
    "success": true,
    "solde": 10000,
    "numero": "221771234567"
}
```

### 4. Historique des transferts

**GET** `/api/transfer/history?page=1&limit=10`

Récupère l'historique des transferts de l'utilisateur authentifié.

#### Paramètres de requête

-   `page` (optional) : Numéro de page (défaut: 1)
-   `limit` (optional) : Nombre d'éléments par page (défaut: 10)

#### Réponse de succès (200)

```json
{
    "success": true,
    "message": "Historique récupéré avec succès",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": "trans_123",
                "type": "transfer_out",
                "amount": -1000,
                "status": "completed",
                "reference": "TRF_9d1c4e82-1234-5678-9012-123456789abc",
                "created_at": "2025-11-09T01:15:00.000000Z"
            }
        ],
        "total": 1,
        "per_page": 10
    }
}
```

## Codes d'erreur

-   `400` : Erreur métier (solde insuffisant, etc.)
-   `401` : Non authentifié
-   `422` : Erreur de validation
-   `500` : Erreur serveur

## Exemples d'utilisation

### Transfert réussi

```bash
curl -X POST "http://your-api.com/api/transfer" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "numero": "221771234568",
    "montant": 1000
  }'
```

### Vérification de numéro

```bash
curl -X POST "http://your-api.com/api/transfer/check-number" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "numero": "221771234568"
  }'
```
