<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

/**
 * Trait ApiResponse
 *
 * Centralise la gestion des réponses API pour maintenir une cohérence
 * dans le format des réponses de succès et d'erreur à travers l'application.
 *
 * @package App\Traits
 */
trait ApiResponse
{
    /**
     * Réponse de succès standardisée
     *
     * @param mixed $data Données à retourner
     * @param string $message Message de succès
     * @param int $status Code de statut HTTP
     * @param array $headers Headers supplémentaires
     * @return JsonResponse
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Opération réussie',
        int $status = Response::HTTP_OK,
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        // Ajouter les données seulement si elles ne sont pas nulles
        if ($data !== null) {
            if (is_array($data) && !empty($data)) {
                $response = array_merge($response, $data);
            } else {
                $response['data'] = $data;
            }
        }

        return response()->json($response, $status, $headers);
    }

    /**
     * Réponse d'erreur standardisée
     *
     * @param string $message Message d'erreur
     * @param int $status Code de statut HTTP
     * @param mixed $errors Détails des erreurs (pour validation, etc.)
     * @param string|null $errorCode Code d'erreur spécifique
     * @param array $extra Données supplémentaires
     * @param array $headers Headers supplémentaires
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message = 'Une erreur est survenue',
        int $status = Response::HTTP_BAD_REQUEST,
        mixed $errors = null,
        ?string $errorCode = null,
        array $extra = [],
        array $headers = []
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        // Ajouter le code d'erreur si fourni
        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }

        // Ajouter les détails des erreurs si fournis
        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        // Fusionner les données supplémentaires
        if (!empty($extra)) {
            $response = array_merge($response, $extra);
        }

        return response()->json($response, $status, $headers);
    }

    /**
     * Réponse d'erreur de validation
     *
     * @param mixed $errors Erreurs de validation
     * @param string $message Message d'erreur personnalisé
     * @return JsonResponse
     */
    protected function validationErrorResponse(
        mixed $errors,
        string $message = 'Erreur de validation'
    ): JsonResponse {
        return $this->errorResponse(
            message: $message,
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            errors: $errors,
            errorCode: 'validation_error'
        );
    }

    /**
     * Réponse d'erreur d'authentification
     *
     * @param string $message Message d'erreur
     * @return JsonResponse
     */
    protected function unauthorizedResponse(string $message = 'Non authentifié'): JsonResponse
    {
        return $this->errorResponse(
            message: $message,
            status: Response::HTTP_UNAUTHORIZED,
            errorCode: 'unauthenticated'
        );
    }

    /**
     * Réponse d'erreur d'autorisation (accès interdit)
     *
     * @param string $message Message d'erreur
     * @return JsonResponse
     */
    protected function forbiddenResponse(string $message = 'Accès interdit'): JsonResponse
    {
        return $this->errorResponse(
            message: $message,
            status: Response::HTTP_FORBIDDEN,
            errorCode: 'access_forbidden'
        );
    }

    /**
     * Réponse pour ressource non trouvée
     *
     * @param string $message Message d'erreur
     * @return JsonResponse
     */
    protected function notFoundResponse(string $message = 'Ressource non trouvée'): JsonResponse
    {
        return $this->errorResponse(
            message: $message,
            status: Response::HTTP_NOT_FOUND,
            errorCode: 'resource_not_found'
        );
    }

    /**
     * Réponse pour erreur serveur interne
     *
     * @param string $message Message d'erreur
     * @param mixed $debug Informations de débogage (uniquement en développement)
     * @return JsonResponse
     */
    protected function serverErrorResponse(
        string $message = 'Erreur interne du serveur',
        mixed $debug = null
    ): JsonResponse {
        $extra = [];

        // Ajouter les informations de débogage uniquement en environnement de développement
        if (app()->environment(['local', 'testing']) && $debug !== null) {
            $extra['debug'] = $debug;
        }

        return $this->errorResponse(
            message: $message,
            status: Response::HTTP_INTERNAL_SERVER_ERROR,
            errorCode: 'internal_server_error',
            extra: $extra
        );
    }

    /**
     * Réponse de création réussie
     *
     * @param mixed $data Données créées
     * @param string $message Message de succès
     * @return JsonResponse
     */
    protected function createdResponse(
        mixed $data = null,
        string $message = 'Ressource créée avec succès'
    ): JsonResponse {
        return $this->successResponse($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Réponse de suppression réussie
     *
     * @param string $message Message de succès
     * @return JsonResponse
     */
    protected function deletedResponse(string $message = 'Ressource supprimée avec succès'): JsonResponse
    {
        return $this->successResponse(null, $message, Response::HTTP_OK);
    }

    /**
     * Réponse avec données paginées
     *
     * @param mixed $data Données paginées
     * @param array $pagination Informations de pagination
     * @param string $message Message de succès
     * @return JsonResponse
     */
    protected function paginatedResponse(
        mixed $data,
        array $pagination,
        string $message = 'Données récupérées avec succès'
    ): JsonResponse {
        return $this->successResponse([
            'data' => $data,
            'pagination' => $pagination
        ], $message);
    }

    /**
     * Réponse pour compte bloqué temporairement
     *
     * @param string $message Message d'erreur
     * @param array $extra Données supplémentaires (tentatives, durée de blocage, etc.)
     * @return JsonResponse
     */
    protected function accountBlockedResponse(
        string $message = 'Compte temporairement bloqué',
        array $extra = []
    ): JsonResponse {
        return $this->errorResponse(
            message: $message,
            status: Response::HTTP_LOCKED,
            errorCode: 'account_blocked',
            extra: $extra
        );
    }

    /**
     * Réponse pour solde insuffisant
     *
     * @param string $message Message d'erreur
     * @param float|null $currentBalance Solde actuel (optionnel)
     * @param float|null $requiredAmount Montant requis (optionnel)
     * @return JsonResponse
     */
    protected function insufficientBalanceResponse(
        string $message = 'Solde insuffisant',
        ?float $currentBalance = null,
        ?float $requiredAmount = null
    ): JsonResponse {
        $extra = [];

        if ($currentBalance !== null) {
            $extra['current_balance'] = $currentBalance;
        }

        if ($requiredAmount !== null) {
            $extra['required_amount'] = $requiredAmount;
        }

        return $this->errorResponse(
            message: $message,
            status: Response::HTTP_BAD_REQUEST,
            errorCode: 'insufficient_balance',
            extra: $extra
        );
    }

    /**
     * Gestion automatique des exceptions en réponses API
     *
     * @param Throwable $exception Exception à traiter
     * @param string|null $customMessage Message personnalisé
     * @return JsonResponse
     */
    protected function handleException(Throwable $exception, ?string $customMessage = null): JsonResponse
    {
        // Message par défaut
        $message = $customMessage ?? $exception->getMessage();

        // Gestion spécifique selon le type d'exception
        return match (true) {
            $exception instanceof ValidationException => $this->validationErrorResponse(
                $exception->errors(),
                $message ?: 'Erreur de validation'
            ),

            $exception instanceof ModelNotFoundException,
            $exception instanceof NotFoundHttpException => $this->notFoundResponse(
                $message ?: 'Ressource non trouvée'
            ),

            $exception instanceof UnauthorizedHttpException => $this->unauthorizedResponse(
                $message ?: 'Non authentifié'
            ),

            // Exception générique
            default => $this->serverErrorResponse(
                $message ?: 'Une erreur inattendue est survenue',
                app()->environment(['local', 'testing']) ? [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString()
                ] : null
            )
        };
    }

    /**
     * Réponse avec données et métadonnées
     *
     * @param mixed $data Données principales
     * @param array $meta Métadonnées
     * @param string $message Message de succès
     * @param int $status Code de statut HTTP
     * @return JsonResponse
     */
    protected function responseWithMeta(
        mixed $data,
        array $meta = [],
        string $message = 'Opération réussie',
        int $status = Response::HTTP_OK
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $status);
    }
}
