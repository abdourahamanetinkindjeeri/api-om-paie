<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Repositories\UserRepository;
use App\Models\User;
use App\Services\Contracts\OtpServiceInterface;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private UserRepository $users) {}

    public function register(array $data): User
    {
        // Pas besoin de Hash::make ici, le mutateur User::setCodeAttribute s’en charge
        return $this->users->create($data);
    }

    public function initiateLogin(string $telephone, string $password): ?array
    {
        $user = $this->users->findByTelephone($telephone);

        if (!$user) {
            return null;
        }

        // Vérifier si le compte est bloqué
        if ($user->isBlocked()) {
            return $this->blockedResponse($user, 'Compte bloqué temporairement');
        }

        // Vérifier le code PIN
        if (!Hash::check($password, $user->code)) {
            $user->incrementLoginAttempts();

            if ($user->isBlocked()) {
                return $this->blockedResponse($user, 'Compte bloqué après ' . User::MAX_LOGIN_ATTEMPTS . ' tentatives incorrectes');
            }

            return [
                'error' => 'invalid_credentials',
                'message' => 'Code incorrect',
                'attempts_remaining' => $user->remainingAttempts(),
            ];
        }

        // Code PIN correct, envoyer OTP pour 2FA
        $otpService = app(OtpServiceInterface::class);
        $otpSent = $otpService->generateAndSend($telephone, OtpCode::PURPOSE_LOGIN);

        if (!$otpSent) {
            return [
                'error' => 'otp_send_failed',
                'message' => 'Impossible d\'envoyer le code OTP',
            ];
        }

        // Récupérer le code OTP généré pour le retourner dans la réponse (pour les tests)
        $otpCode = OtpCode::where('identifier', $telephone)
            ->where('purpose', OtpCode::PURPOSE_LOGIN)
            ->whereNull('used_at')
            ->orderBy('created_at', 'desc')
            ->first();

        return [
            'success' => true,
            'message' => 'Code OTP envoyé pour la connexion',
            'telephone' => $telephone,
            'code_otp' => $otpCode ? $otpCode->code : null,
            'expires_in_minutes' => \App\Models\OtpCode::DEFAULT_EXPIRY_MINUTES
        ];
    }

    public function confirmLogin(string $telephone, string $otpCode): ?array
    {
        $user = $this->users->findByTelephone($telephone);

        if (!$user) {
            return null;
        }

        $otpService = app(\App\Services\Contracts\OtpServiceInterface::class);
        if (!$otpService->verify($telephone, $otpCode, \App\Models\OtpCode::PURPOSE_LOGIN)) {
            return [
                'error' => 'invalid_otp',
                'message' => 'Code OTP invalide ou expiré',
            ];
        }

        // Connexion réussie avec 2FA
        $user->resetLoginAttempts();

        $tokenResult = $user->createToken('auth_token');

        return [
            'user'                 => $user,
            'access_token'         => $tokenResult->accessToken,
            'token_type'           => 'Bearer',
            'expires_at'           => $tokenResult->token->expires_at,
            'expires_in'           => 15 * 60, // 15 minutes
            'refresh_token'        => $tokenResult->refreshToken ?? null,
            'refresh_expires_at'   => $tokenResult->refresh_expires_at ?? null,
        ];
    }

    private function blockedResponse(User $user, string $message): array
    {
        return [
            'error'             => 'account_blocked',
            'message'           => $message,
            'blocked_until'     => $user->blocked_until,
            'remaining_minutes' => $user->getBlockedTimeRemaining(),
        ];
    }
}
