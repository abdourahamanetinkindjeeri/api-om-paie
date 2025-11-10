# QR Code API - OM-Paie

Cette API permet de générer et scanner des QR codes pour les utilisateurs de l'application OM-Paie.

## Fonctionnalités

### 1. Génération du QR code utilisateur

**Endpoint:** `GET /api/user/qrcode`

-   **Authentication:** Required (Bearer Token)
-   **Description:** Génère un QR code SVG pour l'utilisateur connecté
-   **Response:** Image SVG contenant le QR code

**Exemple:**

```bash
curl -X GET http://localhost:8000/api/user/qrcode \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "accept: image/svg+xml"
```

### 2. Récupération des données QR code

**Endpoint:** `GET /api/user/qrcode/data`

-   **Authentication:** Required (Bearer Token)
-   **Description:** Récupère les données JSON qui seront encodées dans le QR code
-   **Response:** JSON avec les informations utilisateur

**Exemple:**

```bash
curl -X GET http://localhost:8000/api/user/qrcode/data \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

**Response:**

```json
{
    "success": true,
    "message": "Données du QR code récupérées avec succès",
    "data": {
        "user_id": "a052d01d-6e5a-4b3f-bf64-9ca5c5243dfb",
        "telephone": "+221771234567",
        "nom_complet": "System Admin",
        "type": "om_paie_user",
        "timestamp": "2025-11-10T23:31:19.102280Z",
        "qr_string": "{\"user_id\":\"...\",\"telephone\":\"...\",\"nom_complet\":\"...\",\"type\":\"om_paie_user\",\"timestamp\":\"...\"}"
    }
}
```

### 3. Scanner un QR code

**Endpoint:** `POST /api/qrcode/scan`

-   **Authentication:** Required (Bearer Token)
-   **Description:** Décode et valide un QR code OM-Paie
-   **Body:** JSON avec les données du QR code scanné

**Exemple:**

```bash
curl -X POST http://localhost:8000/api/qrcode/scan \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "qr_data": "{\"user_id\":\"a052d01d-6e5a-4b3f-bf64-9ca5c5243dfb\",\"telephone\":\"+221771234567\",\"nom_complet\":\"System Admin\",\"type\":\"om_paie_user\",\"timestamp\":\"2025-11-10T23:31:19.102280Z\"}"
  }'
```

**Response:**

```json
{
    "success": true,
    "message": "QR code décodé avec succès",
    "data": {
        "user_id": "a052d01d-6e5a-4b3f-bf64-9ca5c5243dfb",
        "telephone": "+221771234567",
        "nom_complet": "System Admin",
        "type": "om_paie_user",
        "timestamp": "2025-11-10T23:31:19.102280Z"
    },
    "user_info": {
        "id": "a052d01d-6e5a-4b3f-bf64-9ca5c5243dfb",
        "nom": "Admin",
        "prenom": "System",
        "telephone": "+221771234567"
    }
}
```

## Structure des données QR code

Chaque QR code OM-Paie contient les informations suivantes au format JSON :

```json
{
    "user_id": "UUID de l'utilisateur",
    "telephone": "Numéro de téléphone au format international",
    "nom_complet": "Prénom + Nom de l'utilisateur",
    "type": "om_paie_user",
    "timestamp": "Date/heure de génération au format ISO"
}
```

## Cas d'usage

### 1. Paiement entre utilisateurs

-   L'utilisateur A génère son QR code
-   L'utilisateur B scanne le QR code de A
-   B peut alors initier un transfert vers A en utilisant les informations décodées

### 2. Identification rapide

-   Affichage du QR code comme moyen d'identification
-   Scan rapide pour obtenir les informations de contact

### 3. Intégration dans l'app mobile

-   Génération du QR code à afficher dans le profil utilisateur
-   Scanner intégré pour lire les QR codes d'autres utilisateurs

## Sécurité

-   Tous les endpoints nécessitent une authentification Bearer Token
-   Les QR codes contiennent un timestamp pour éviter la réutilisation
-   Validation du format et du type de QR code lors du scan
-   Vérification de l'existence de l'utilisateur en base de données

## Format de sortie

-   **QR Code Image:** Format SVG (vectoriel, redimensionnable)
-   **Content-Type:** `image/svg+xml`
-   **Encoding:** UTF-8

## Erreurs possibles

### 400 Bad Request

-   QR code invalide ou format incorrect
-   QR code non reconnu par OM-Paie

### 401 Unauthorized

-   Token manquant ou invalide

### 404 Not Found

-   Utilisateur référencé dans le QR code non trouvé

### 500 Internal Server Error

-   Erreur lors de la génération ou du décodage
