<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service de limitation de taux (rate limiting) pour sécuriser les opérations sensibles
 */
class RateLimiter
{
    /**
     * Limites par défaut pour différents types d'opérations
     */
    private const LIMITS = [
        'otp_generation' => [
            'max_attempts' => 5,
            'window_minutes' => 15,
            'block_minutes' => 30
        ],
        'otp_verification' => [
            'max_attempts' => 3,
            'window_minutes' => 5,
            'block_minutes' => 15
        ],
        'login_attempts' => [
            'max_attempts' => 5,
            'window_minutes' => 15,
            'block_minutes' => 30
        ],
        'payment_attempts' => [
            'max_attempts' => 3,
            'window_minutes' => 10,
            'block_minutes' => 60
        ],
        'transfer_attempts' => [
            'max_attempts' => 3,
            'window_minutes' => 10,
            'block_minutes' => 60
        ]
    ];

    /**
     * Vérifie si une opération est autorisée (pas en rate limit)
     *
     * @param string $key Clé unique pour l'opération (ex: "otp:user_id", "login:ip")
     * @param string $type Type d'opération (otp_generation, login_attempts, etc.)
     * @return array ['allowed' => bool, 'remaining_attempts' => int, 'blocked_until' => timestamp|null]
     */
    public function check(string $key, string $type = 'otp_generation'): array
    {
        $config = self::LIMITS[$type] ?? self::LIMITS['otp_generation'];

        $cacheKey = "rate_limit:{$type}:{$key}";
        $blockKey = "rate_limit_blocked:{$type}:{$key}";

        // Vérifier si l'utilisateur est bloqué
        $blockedUntil = Cache::get($blockKey);
        if ($blockedUntil && $blockedUntil > now()->timestamp) {
            return [
                'allowed' => false,
                'remaining_attempts' => 0,
                'blocked_until' => $blockedUntil,
                'block_reason' => 'rate_limit_exceeded'
            ];
        }

        // Récupérer le compteur actuel
        $attempts = Cache::get($cacheKey, 0);
        $remainingAttempts = max(0, $config['max_attempts'] - $attempts);

        if ($attempts >= $config['max_attempts']) {
            // Bloquer l'utilisateur
            $blockedUntil = now()->addMinutes($config['block_minutes'])->timestamp;
            Cache::put($blockKey, $blockedUntil, $config['block_minutes']);

            Log::warning("Rate limit exceeded for {$type}", [
                'key' => $key,
                'attempts' => $attempts,
                'blocked_until' => date('Y-m-d H:i:s', $blockedUntil)
            ]);

            return [
                'allowed' => false,
                'remaining_attempts' => 0,
                'blocked_until' => $blockedUntil,
                'block_reason' => 'rate_limit_exceeded'
            ];
        }

        return [
            'allowed' => true,
            'remaining_attempts' => $remainingAttempts - 1, // -1 car on va incrémenter après
            'blocked_until' => null
        ];
    }

    /**
     * Enregistre une tentative d'opération
     *
     * @param string $key Clé unique pour l'opération
     * @param string $type Type d'opération
     * @return bool True si l'opération a été enregistrée
     */
    public function recordAttempt(string $key, string $type = 'otp_generation'): bool
    {
        $config = self::LIMITS[$type] ?? self::LIMITS['otp_generation'];
        $cacheKey = "rate_limit:{$type}:{$key}";

        $attempts = Cache::get($cacheKey, 0);
        $attempts++;

        Cache::put($cacheKey, $attempts, $config['window_minutes']);

        Log::info("Rate limit attempt recorded for {$type}", [
            'key' => $key,
            'attempts' => $attempts,
            'max_attempts' => $config['max_attempts']
        ]);

        return true;
    }

    /**
     * Réinitialise le compteur pour une clé donnée
     *
     * @param string $key Clé unique pour l'opération
     * @param string $type Type d'opération
     * @return bool
     */
    public function reset(string $key, string $type = 'otp_generation'): bool
    {
        $cacheKey = "rate_limit:{$type}:{$key}";
        $blockKey = "rate_limit_blocked:{$type}:{$key}";

        Cache::forget($cacheKey);
        Cache::forget($blockKey);

        Log::info("Rate limit reset for {$type}", ['key' => $key]);

        return true;
    }

    /**
     * Vérifie et enregistre une tentative en une seule opération
     *
     * @param string $key Clé unique pour l'opération
     * @param string $type Type d'opération
     * @return array Résultat de la vérification
     */
    public function attempt(string $key, string $type = 'otp_generation'): array
    {
        $check = $this->check($key, $type);

        if ($check['allowed']) {
            $this->recordAttempt($key, $type);
            // Recalculer les tentatives restantes après enregistrement
            $check['remaining_attempts'] = max(0, $check['remaining_attempts']);
        }

        return $check;
    }

    /**
     * Obtient les statistiques de rate limiting pour une clé
     *
     * @param string $key Clé unique pour l'opération
     * @param string $type Type d'opération
     * @return array
     */
    public function getStats(string $key, string $type = 'otp_generation'): array
    {
        $config = self::LIMITS[$type] ?? self::LIMITS['otp_generation'];
        $cacheKey = "rate_limit:{$type}:{$key}";
        $blockKey = "rate_limit_blocked:{$type}:{$key}";

        $attempts = Cache::get($cacheKey, 0);
        $blockedUntil = Cache::get($blockKey);

        return [
            'attempts' => $attempts,
            'max_attempts' => $config['max_attempts'],
            'remaining_attempts' => max(0, $config['max_attempts'] - $attempts),
            'window_minutes' => $config['window_minutes'],
            'block_minutes' => $config['block_minutes'],
            'is_blocked' => $blockedUntil && $blockedUntil > now()->timestamp,
            'blocked_until' => $blockedUntil,
            'next_reset' => Cache::has($cacheKey) ? now()->addMinutes($config['window_minutes']) : null
        ];
    }

    /**
     * Nettoie les anciennes entrées de rate limiting (maintenance)
     * À appeler périodiquement via un job ou une commande
     *
     * @return int Nombre d'entrées nettoyées
     */
    public function cleanup(): int
    {
        // Cette méthode pourrait être implémentée pour nettoyer les clés expirées
        // Pour le moment, on retourne 0 car Redis gère automatiquement l'expiration
        Log::info('Rate limiter cleanup called (no-op for Redis-based implementation)');
        return 0;
    }
}
