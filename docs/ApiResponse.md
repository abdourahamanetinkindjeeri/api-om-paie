# ApiResponse Trait - Documentation

## Vue d'ensemble

Le trait `ApiResponse` centralise la gestion des réponses API pour maintenir une cohérence dans le format des réponses à travers toute l'application OM-Paie.

## Installation

Le trait est automatiquement disponible dans tous les contrôleurs qui héritent de `Controller` (classe de base).

```php
use App\Traits\ApiResponse;

class MonController extends Controller
{
    // Le trait ApiResponse est déjà inclus dans Controller
}
```

## Méthodes principales

### Réponses de succès

#### `successResponse()`

```php
return $this->successResponse(
    $data,           // Données à retourner (optionnel)
    $message,        // Message de succès
    $status,         // Code HTTP (défaut: 200)
    $headers         // Headers supplémentaires
);

// Exemple
return $this->successResponse(
    $user,
    'Utilisateur créé avec succès'
);
```

#### `createdResponse()`

```php
return $this->createdResponse($data, 'Ressource créée');
// Équivalent à successResponse() avec status 201
```

#### `paginatedResponse()`

```php
return $this->paginatedResponse(
    $data,
    $pagination,
    'Données récupérées avec succès'
);
```

### Réponses d'erreur

#### `errorResponse()`

```php
return $this->errorResponse(
    $message,        // Message d'erreur
    $status,         // Code HTTP (défaut: 400)
    $errors,         // Détails des erreurs
    $errorCode,      // Code d'erreur spécifique
    $extra,          // Données supplémentaires
    $headers         // Headers supplémentaires
);
```

#### Réponses d'erreur spécialisées

```php
// Authentification requise (401)
return $this->unauthorizedResponse('Utilisateur non authentifié');

// Accès interdit (403)
return $this->forbiddenResponse('Accès refusé');

// Ressource non trouvée (404)
return $this->notFoundResponse('Utilisateur non trouvé');

// Erreur de validation (422)
return $this->validationErrorResponse($errors, 'Données invalides');

// Compte bloqué (423)
return $this->accountBlockedResponse(
    'Compte temporairement bloqué',
    ['attempts' => 3, 'blocked_until' => '2023-12-01 10:30:00']
);

// Solde insuffisant
return $this->insufficientBalanceResponse(
    'Solde insuffisant pour cette transaction',
    $currentBalance,
    $requiredAmount
);

// Erreur serveur (500)
return $this->serverErrorResponse('Erreur interne', $debugInfo);
```

### Gestion automatique des exceptions

```php
try {
    // Code métier
    $result = $this->service->doSomething();
    return $this->successResponse($result);
} catch (Exception $e) {
    // Gestion automatique selon le type d'exception
    return $this->handleException($e);
}
```

## Format des réponses

### Réponse de succès

```json
{
    "success": true,
    "message": "Opération réussie",
    "data": {
        // Données retournées
    }
}
```

### Réponse d'erreur

```json
{
    "success": false,
    "message": "Message d'erreur",
    "error_code": "specific_error_code",
    "errors": {
        // Détails des erreurs (validation, etc.)
    }
}
```

### Réponse paginée

```json
{
    "success": true,
    "message": "Données récupérées",
    "data": [
        // Éléments de la page
    ],
    "pagination": {
        "current_page": 1,
        "per_page": 10,
        "total": 50,
        "last_page": 5
    }
}
```

## Exemples d'utilisation

### Contrôleur de transfert

```php
public function transfer(TransferRequest $request): JsonResponse
{
    try {
        $user = auth()->user();

        if (!$user) {
            return $this->unauthorizedResponse('Utilisateur non authentifié');
        }

        $result = $this->transferService->transfer(
            $user->telephone,
            $request->telephone,
            $request->montant
        );

        return $this->successResponse(
            new TransferResource($result),
            'Transfert effectué avec succès'
        );
    } catch (InsufficientBalanceException $e) {
        return $this->insufficientBalanceResponse(
            $e->getMessage(),
            $e->getCurrentBalance(),
            $e->getRequiredAmount()
        );
    } catch (Exception $e) {
        return $this->handleException($e);
    }
}
```

### Contrôleur d'authentification

```php
public function login(LoginRequest $request): JsonResponse
{
    $result = $this->authService->login(
        $request->telephone,
        $request->code
    );

    if (!$result) {
        return $this->unauthorizedResponse('Utilisateur non trouvé');
    }

    if (isset($result['error'])) {
        return match ($result['error']) {
            'account_blocked' => $this->accountBlockedResponse(
                $result['message'],
                ['attempts' => $result['attempts'], 'blocked_until' => $result['blocked_until']]
            ),
            'invalid_credentials' => $this->unauthorizedResponse($result['message']),
            default => $this->errorResponse($result['message'])
        };
    }

    return $this->successResponse(
        new AuthResource($result['user'], $result['access_token']),
        'Connexion réussie'
    );
}
```

## Gestion globale des exceptions

Le trait est également intégré dans `App\Exceptions\Handler` pour gérer automatiquement toutes les exceptions non capturées et retourner des réponses API cohérentes.

### Types d'exceptions gérées automatiquement

-   `ValidationException` → Réponse de validation (422)
-   `AuthenticationException` → Non authentifié (401)
-   `AccessDeniedHttpException` → Accès interdit (403)
-   `ModelNotFoundException` → Ressource non trouvée (404)
-   `NotFoundHttpException` → Point d'accès non trouvé (404)
-   `MethodNotAllowedHttpException` → Méthode non autorisée (405)
-   `TooManyRequestsHttpException` → Trop de requêtes (429)
-   Exceptions génériques → Erreur serveur (500)

## Bonnes pratiques

1. **Utilisez les méthodes spécialisées** : Préférez `unauthorizedResponse()` à `errorResponse()` avec status 401
2. **Messages clairs** : Utilisez des messages d'erreur explicites et en français
3. **Codes d'erreur** : Utilisez des codes d'erreur spécifiques pour les cas métier
4. **Gestion des exceptions** : Utilisez `handleException()` comme fallback
5. **Environnement** : Les détails techniques sont masqués en production

## Codes d'erreur standard

-   `validation_error` : Erreur de validation des données
-   `unauthenticated` : Utilisateur non authentifié
-   `access_forbidden` : Accès interdit
-   `resource_not_found` : Ressource non trouvée
-   `account_blocked` : Compte temporairement bloqué
-   `insufficient_balance` : Solde insuffisant
-   `internal_server_error` : Erreur interne du serveur
-   `method_not_allowed` : Méthode HTTP non autorisée
-   `rate_limit_exceeded` : Limite de requêtes dépassée
