<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Carbon\Carbon;

class OtpCode extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    // MongoDB connection
    protected $connection = 'mongodb';
    protected $collection = 'otp_codes';

    protected $fillable = [
        'identifier', // téléphone ou email
        'code',
        'purpose', // 'registration', 'password_reset', etc.
        'expires_at',
        'used_at',
        'attempts',
        'max_attempts'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'attempts' => 'integer',
        'max_attempts' => 'integer'
    ];

    public const PURPOSE_REGISTRATION = 'registration';
    public const PURPOSE_PASSWORD_RESET = 'password_reset';
    public const PURPOSE_LOGIN = 'login';

    public const DEFAULT_EXPIRY_MINUTES = 5; // Fallback, use config('ompaie.otp.default_expiry_minutes')
    public const DEFAULT_MAX_ATTEMPTS = 3;

    /**
     * Vérifie si le code OTP est encore valide
     */
    public function isValid(): bool
    {
        return !$this->isExpired() &&
            !$this->isUsed() &&
            !$this->hasExceededAttempts();
    }

    /**
     * Vérifie si le code a expiré
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Vérifie si le code a été utilisé
     */
    public function isUsed(): bool
    {
        return !is_null($this->used_at);
    }

    /**
     * Vérifie si le nombre maximum de tentatives a été dépassé
     */
    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    /**
     * Marque le code comme utilisé
     */
    public function markAsUsed(): bool
    {
        $this->used_at = now();
        return $this->save();
    }

    /**
     * Incrémente le compteur de tentatives
     */
    public function incrementAttempts(): bool
    {
        $this->attempts++;
        return $this->save();
    }

    /**
     * Génère un nouveau code OTP
     */
    public static function generate(string $identifier, string $purpose = self::PURPOSE_REGISTRATION, int $expiryMinutes = null): string
    {
        // Invalider les anciens codes pour cet identifiant et ce purpose
        static::where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        // Générer un nouveau code à 6 chiffres
        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Créer le nouveau code OTP
        static::create([
            'identifier' => $identifier,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes($expiryMinutes ?? config('ompaie.otp.default_expiry_minutes', self::DEFAULT_EXPIRY_MINUTES)),
            'attempts' => 0,
            'max_attempts' => self::DEFAULT_MAX_ATTEMPTS
        ]);

        return $code;
    }

    /**
     * Vérifie un code OTP
     */
    public static function verify(string $identifier, string $code, string $purpose = self::PURPOSE_REGISTRATION): bool
    {
        $otpCode = static::where('identifier', $identifier)
            ->where('code', $code)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->first();

        if (!$otpCode) {
            return false;
        }

        $otpCode->incrementAttempts();

        if (!$otpCode->isValid()) {
            return false;
        }

        $otpCode->markAsUsed();
        return true;
    }

    /**
     * Nettoie les anciens codes OTP expirés
     */
    public static function cleanup(): int
    {
        return static::where('expires_at', '<', now()->subDay())->delete();
    }
}
