<?php

namespace App\Services;

use App\Models\OtpCode;
use App\Repositories\UserRepository;
use App\Models\User;
use App\Services\Contracts\OtpServiceInterface;
use App\Services\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use DateTimeImmutable;

class AuthService
{
    public function __construct(
        private UserRepository $users,
        private RateLimiter $rateLimiter
    ) {}

    public function register(array $data): User
    {
        // Pas besoin de Hash::make ici, le mutateur User::setCodeAttribute s’en charge
        return $this->users->create($data);
    }

    public function initiateLogin(string $telephone, string $password): ?array
    {
        // Vérifier les limites de taux pour les tentatives de connexion
        $rateLimitKey = "login:{$telephone}";
        $rateCheck = $this->rateLimiter->attempt($rateLimitKey, 'login_attempts');

        if (!$rateCheck['allowed']) {
            Log::warning("Login rate limit exceeded", [
                'telephone' => $telephone,
                'blocked_until' => $rateCheck['blocked_until']
            ]);

            return [
                'error' => 'rate_limit_exceeded',
                'message' => 'Trop de tentatives de connexion. Veuillez réessayer plus tard.',
                'blocked_until' => $rateCheck['blocked_until'],
                'remaining_attempts' => $rateCheck['remaining_attempts']
            ];
        }

        $user = $this->users->findByTelephone($telephone);

        if (!$user) {
            // Enregistrer la tentative même si l'utilisateur n'existe pas (sécurité)
            $this->rateLimiter->recordAttempt($rateLimitKey, 'login_attempts');
            return null;
        }

        // Vérifier si le compte est bloqué
        if ($user->isBlocked()) {
            return $this->blockedResponse($user, 'Compte bloqué temporairement');
        }

        // Vérifier le code PIN
        if (!Hash::check($password, $user->code)) {
            $user->incrementLoginAttempts();
            // Enregistrer la tentative de connexion échouée
            $this->rateLimiter->recordAttempt($rateLimitKey, 'login_attempts');

            if ($user->isBlocked()) {
                return $this->blockedResponse($user, 'Compte bloqué après ' . User::MAX_LOGIN_ATTEMPTS . ' tentatives incorrectes');
            }

            return [
                'error' => 'invalid_credentials',
                'message' => 'Code incorrect',
                'attempts_remaining' => $user->remainingAttempts(),
                'remaining_rate_attempts' => $rateCheck['remaining_attempts']
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
        // Réinitialiser le compteur de rate limiting
        $rateLimitKey = "login:{$telephone}";
        $this->rateLimiter->reset($rateLimitKey, 'login_attempts');

        $tokenResult = $user->createToken('auth_token');

        // Vérifier que la clé secrète Passport est configurée
        $passportSecret = config('passport.secret');
        if (!$passportSecret) {
            Log::error('Clé secrète Passport non configurée');
            return [
                'error' => 'configuration_error',
                'message' => 'Service temporairement indisponible',
            ];
        }

        // Générer un JWT refresh token signé avec HS256
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($passportSecret)
        );

        $now = new DateTimeImmutable();
        $refreshExpires = $now->modify('+30 days'); // Expiration du refresh token

        $refreshToken = $config->builder()
            ->issuedBy(config('app.url'))
            ->permittedFor(config('app.url'))
            ->identifiedBy($tokenResult->token->id . '-refresh')
            ->issuedAt($now)
            ->expiresAt($refreshExpires)
            ->withClaim('user_id', $user->id)
            ->withClaim('token_id', $tokenResult->token->id)
            ->getToken($config->signer(), $config->signingKey());

        return [
            'user'                 => $user,
            'access_token'         => $tokenResult->accessToken,
            'token_type'           => 'Bearer',
            'expires_at'           => $tokenResult->token->expires_at,
            'expires_in'           => 15 * 60, // 15 minutes
            'refresh_token'        => $refreshToken->toString(),
            'refresh_expires_at'   => $refreshExpires,
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
