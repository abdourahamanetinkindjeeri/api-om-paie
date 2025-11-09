# API de Transfert - Documentation

## Vue d'ensemble

Cette API permet d'effectuer des transferts d'argent entre utilisateurs du système OM-Paie. Elle est basée sur l'architecture BaseService/BaseRepository existante.

## Authentification

Toutes les routes de transfert nécessitent une authentification via Laravel Passport. Incluez le token Bearer dans l'en-tête Authorization :

```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

## Endpoints

### 1. Effectuer un transfert

**POST** `/api/transfer`

Effectue un transfert d'argent de l'utilisateur authentifié vers un autre utilisateur.

#### Request Body

```json
{
    "numero": "221771234568",
    "montant": 1000
}
```

#### Paramètres

-   `numero` (string, requis) : Numéro du destinataire (doit exister dans la table users)
-   `montant` (numeric, requis) : Montant à transférer (min: 1, max: 1,000,000)

#### Réponse de succès (200)

```json
{
    "success": true,
    "message": "Transfert effectué avec succès",
    "data": {
        "success": true,
        "reference": "TRF_550e8400-e29b-41d4-a716-446655440000",
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
        "date_transfert": "2025-11-09T01:30:00.000Z",
        "statut": "completed"
    }
}
```

#### Erreurs possibles

-   **400** - Solde insuffisant
-   **401** - Non authentifié
-   **422** - Erreurs de validation (numéro inexistant, montant invalide)

### 2. Vérifier l'existence d'un numéro

**POST** `/api/transfer/check-number`

Vérifie si un numéro de téléphone existe dans le système.

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

-   `page` (int, optionnel) : Numéro de page (défaut: 1)
-   `limit` (int, optionnel) : Nombre d'éléments par page (défaut: 10)

#### Réponse de succès (200)

```json
{
    "success": true,
    "message": "Historique récupéré avec succès",
    "data": [
        {
            "id": "trans_123",
            "type": "transfer_out",
            "amount": -1000,
            "status": "completed",
            "reference": "TRF_550e8400-e29b-41d4-a716-446655440000",
            "created_at": "2025-11-09T01:30:00.000Z",
            "meta": {
                "sender_number": "221771234567",
                "receiver_number": "221771234568",
                "transfer_type": "debit"
            }
        }
    ]
}
```

## Règles métier

1. **Solde suffisant** : L'expéditeur doit avoir un solde suffisant pour effectuer le transfert
2. **Utilisateurs différents** : Impossible de faire un transfert vers soi-même
3. **Atomicité** : Les transferts sont effectués dans une transaction pour garantir la cohérence
4. **Traçabilité** : Chaque transfert génère deux transactions liées par une référence unique
5. **Limites** : Montant minimum de 1, maximum de 1,000,000

## Structure des données

### Types de transactions

-   `transfer_out` : Transaction de débit (expéditeur)
-   `transfer_in` : Transaction de crédit (destinataire)

### Référence de transfert

Format : `TRF_` suivi d'un UUID unique

### Métadonnées

Chaque transaction contient des métadonnées avec :

-   `sender_number` : Numéro de l'expéditeur
-   `receiver_number` : Numéro du destinataire
-   `transfer_type` : Type de transaction ("debit" ou "credit")

## Exemples d'utilisation

### Effectuer un transfert avec cURL

```bash
curl -X POST http://localhost:8000/api/transfer \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "numero": "221771234568",
    "montant": 1000
  }'
```

### Vérifier un numéro avec cURL

```bash
curl -X POST http://localhost:8000/api/transfer/check-number \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "numero": "221771234568"
  }'
```

### Obtenir le solde avec cURL

```bash
curl -X GET http://localhost:8000/api/transfer/balance \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```
