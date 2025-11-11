<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponse;

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'code',
        'code_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // Si la requête attend une réponse JSON (API)
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Render an exception for API requests.
     *
     * @param Request $request
     * @param Throwable $e
     * @return JsonResponse
     */
    protected function renderApiException(Request $request, Throwable $e): JsonResponse
    {
        return match (true) {
            // Erreurs de validation
            $e instanceof ValidationException => $this->validationErrorResponse(
                $e->errors(),
                'Erreur de validation des données'
            ),

            // Erreurs d'authentification
            $e instanceof AuthenticationException => $this->unauthorizedResponse(
                'Authentification requise'
            ),

            // Accès interdit
            $e instanceof AccessDeniedHttpException => $this->forbiddenResponse(
                'Accès interdit à cette ressource'
            ),

            // Ressource non trouvée
            $e instanceof ModelNotFoundException => $this->notFoundResponse(
                'Ressource non trouvée'
            ),

            $e instanceof NotFoundHttpException => $this->notFoundResponse(
                'Point d\'accès non trouvé'
            ),

            // Méthode non autorisée
            $e instanceof MethodNotAllowedHttpException => $this->errorResponse(
                'Méthode HTTP non autorisée',
                Response::HTTP_METHOD_NOT_ALLOWED,
                null,
                'method_not_allowed'
            ),

            // Trop de requêtes
            $e instanceof TooManyRequestsHttpException => $this->errorResponse(
                'Trop de requêtes. Veuillez réessayer plus tard.',
                Response::HTTP_TOO_MANY_REQUESTS,
                null,
                'rate_limit_exceeded',
                [
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? null
                ]
            ),

            // Erreur générique
            default => $this->handleGenericException($e)
        };
    }

    /**
     * Handle generic exceptions.
     *
     * @param Throwable $e
     * @return JsonResponse
     */
    protected function handleGenericException(Throwable $e): JsonResponse
    {
        // Ne pas exposer les détails des erreurs en production
        $message = app()->environment(['local', 'testing'])
            ? $e->getMessage()
            : 'Une erreur inattendue est survenue';

        $debug = null;
        if (app()->environment(['local', 'testing'])) {
            $debug = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
        }

        return $this->serverErrorResponse($message, $debug);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     * @return Response
     */
    protected function unauthenticated($request, AuthenticationException $exception): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->unauthorizedResponse('Token d\'authentification manquant ou invalide');
        }

        return redirect()->guest($exception->redirectTo() ?? route('login'));
    }
}
