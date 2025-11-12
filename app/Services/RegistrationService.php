<?php

namespace App\Services;

use App\Services\Contracts\RegistrationServiceInterface;
use App\Services\Contracts\OtpServiceInterface;
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Models\OtpCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class RegistrationService implements RegistrationServiceInterface
{
    public function __construct(
        private OtpServiceInterface $otpService,
        private UserRepository $userRepository,
        private WalletRepository $walletRepository
    ) {}

    /**
     * Initie le processus d'enregistrement d'un utilisateur
     */
    public function initiateRegistration(array $userData): array
    {
        // Validation des données utilisateur
        $this->validateUserData($userData);

        // Vérifier les doublons
        $this->checkForDuplicates($userData);

        try {
            // Stocker temporairement les données de l'utilisateur en cache
            $identifier = $userData['telephone'] ?? $userData['email'];
            $cacheKey = 'registration_data_' . md5($identifier);
            Cache::put($cacheKey, $userData, now()->addMinutes(10));

            // Envoyer le code OTP
            $otpSent = $this->otpService->generateAndSend(
                $identifier,
                OtpCode::PURPOSE_REGISTRATION
            );

            if (!$otpSent) {
                throw new \Exception('Impossible d\'envoyer le code OTP');
            }

            Log::info('Processus d\'enregistrement initié', [
                'identifier' => $identifier
            ]);

            return [
                'success' => true,
                'message' => 'Code OTP envoyé avec succès',
                'identifier' => $identifier,
                'expires_in_minutes' => OtpCode::DEFAULT_EXPIRY_MINUTES
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'initiation de l\'enregistrement', [
                'identifier' => $identifier ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Erreur lors de l\'envoi du code OTP: ' . $e->getMessage());
        }
    }

    /**
     * Confirme l'enregistrement avec le code OTP
     */
    public function confirmRegistration(string $identifier, string $otpCode, array $userData = []): array
    {
        try {
            // Vérifier le code OTP
            if (!$this->otpService->verify($identifier, $otpCode, OtpCode::PURPOSE_REGISTRATION)) {
                throw new \Exception('Code OTP invalide ou expiré');
            }

            // Récupérer les données temporaires du cache
            $cacheKey = 'registration_data_' . md5($identifier);
            $cachedUserData = Cache::get($cacheKey);

            if (!$cachedUserData) {
                throw new \Exception('Données d\'enregistrement expirées. Veuillez recommencer.');
            }

            // Fusionner avec les nouvelles données si fournies
            $finalUserData = array_merge($cachedUserData, $userData);

            // Créer l'utilisateur (sans transaction car MongoDB simple ne les supporte pas)
            $user = $this->userRepository->create([
                'telephone' => $finalUserData['telephone'] ?? null,
                'email' => $finalUserData['email'] ?? null,
                'nom' => $finalUserData['nom'] ?? '',
                'prenom' => $finalUserData['prenom'] ?? '',
                'type_piece' => $finalUserData['type_piece'] ?? null,
                'numero' => $finalUserData['numero'] ?? null,
                'adresse' => $finalUserData['adresse'] ?? null,
                'code' => $finalUserData['code'] ?? '1234', // Code par défaut
            ]);

            // Créer le wallet associé
            $wallet = $this->walletRepository->createForUser($user->id);

            // Supprimer les données temporaires du cache
            $cacheKey = 'registration_data_' . md5($identifier);
            Cache::forget($cacheKey);

            Log::info('Utilisateur et wallet créés avec succès', [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'identifier' => $identifier
            ]);

            return [
                'success' => true,
                'message' => 'Compte créé avec succès',
                'user' => [
                    'id' => $user->id,
                    'telephone' => $user->telephone,
                    'email' => $user->email,
                    'nom' => $user->nom,
                    'prenom' => $user->prenom,
                    'wallet_id' => $wallet->id
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la confirmation d\'enregistrement', [
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Erreur lors de la création du compte: ' . $e->getMessage());
        }
    }

    /**
     * Valide les données utilisateur
     */
    private function validateUserData(array $userData): void
    {
        // Au moins un des deux (téléphone ou email) doit être fourni
        if (empty($userData['telephone']) && empty($userData['email'])) {
            throw new ValidationException('Le téléphone ou l\'email est obligatoire');
        }

        // Validation du format de téléphone s'il est fourni
        if (!empty($userData['telephone']) && !preg_match('/^\+221[0-9]{9}$/', $userData['telephone'])) {
            throw new ValidationException('Format de téléphone invalide (+221xxxxxxxxx)');
        }

        // Validation de l'email s'il est fourni
        if (!empty($userData['email']) && !filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Format d\'email invalide');
        }
    }

    /**
     * Vérifie les doublons
     */
    private function checkForDuplicates(array $userData): void
    {
        if (!empty($userData['telephone']) && $this->userRepository->existsByTelephone($userData['telephone'])) {
            throw new ValidationException('Un compte existe déjà avec ce numéro de téléphone');
        }

        if (!empty($userData['email']) && $this->userRepository->existsByEmail($userData['email'])) {
            throw new ValidationException('Un compte existe déjà avec cet email');
        }

        if (!empty($userData['numero']) && $this->userRepository->existsByNumero($userData['numero'])) {
            throw new ValidationException('Un compte existe déjà avec ce numéro de pièce d\'identité');
        }
    }

    /**
     * Renvoie le code OTP d'enregistrement
     */
    public function resendOtp(string $identifier): array
    {
        try {
            // Vérifier que les données d'enregistrement existent toujours en cache
            $cacheKey = 'registration_data_' . md5($identifier);
            $cachedUserData = Cache::get($cacheKey);

            if (!$cachedUserData) {
                throw new \Exception('Session d\'enregistrement expirée. Veuillez recommencer le processus.');
            }

            // Renvoyer le code OTP
            $otpSent = $this->otpService->generateAndSend(
                $identifier,
                OtpCode::PURPOSE_REGISTRATION
            );

            if (!$otpSent) {
                throw new \Exception('Impossible d\'envoyer le code OTP');
            }

            Log::info('Code OTP renvoyé', [
                'identifier' => $identifier
            ]);

            return [
                'success' => true,
                'message' => 'Code OTP renvoyé avec succès',
                'identifier' => $identifier,
                'expires_in_minutes' => OtpCode::DEFAULT_EXPIRY_MINUTES
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors du renvoi du code OTP', [
                'identifier' => $identifier,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Erreur lors du renvoi du code OTP: ' . $e->getMessage());
        }
    }
}
