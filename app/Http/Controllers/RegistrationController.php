<?php

namespace App\Http\Controllers;

use App\Http\Requests\InitiateRegistrationRequest;
use App\Http\Requests\ConfirmRegistrationRequest;
use App\Services\Contracts\RegistrationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function __construct(
        private RegistrationServiceInterface $registrationService
    ) {}

    /**
     * Initie le processus d'enregistrement
     *
     * @param InitiateRegistrationRequest $request
     * @return JsonResponse
     */
    public function initiate(InitiateRegistrationRequest $request): JsonResponse
    {
        try {
            $result = $this->registrationService->initiateRegistration($request->validated());

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Code OTP envoyé avec succès'
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'initiation de l\'enregistrement', [
                'request' => $request->validated(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirme l'enregistrement avec le code OTP
     *
     * @param ConfirmRegistrationRequest $request
     * @return JsonResponse
     */
    public function confirm(ConfirmRegistrationRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Séparer l'identifier et le code OTP des autres données
            $identifier = $validated['identifier'];
            $otpCode = $validated['otp_code'];

            // Récupérer les données additionnelles
            $additionalData = array_diff_key($validated, ['identifier' => '', 'otp_code' => '']);

            $result = $this->registrationService->confirmRegistration(
                $identifier,
                $otpCode,
                $additionalData
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Compte créé avec succès'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => ['otp_code' => [$e->getMessage()]]
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la confirmation de l\'enregistrement', [
                'identifier' => $validated['identifier'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
