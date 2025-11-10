<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Authenticatable;

class User extends Model
{
    use HasFactory, Notifiable, HasUuids, HasApiTokens, Authenticatable;

    public const MAX_LOGIN_ATTEMPTS = 3;
    public const BLOCK_DURATION_MINUTES = 30;

    protected $keyType = 'string';
    public $incrementing = false;

    // Use the mongodb connection
    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'nom',
        'prenom',
        'type_piece',
        'numero',
        'adresse',
        'code',
        'telephone',
        'email',
        'email_verified_at',
        'login_attempts',
        'blocked_until',
        'last_login_attempt',
    ];

    protected $hidden = ['code', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'blocked_until' => 'datetime',
        'last_login_attempt' => 'datetime',
    ];

    /**
     * Mutateur pour hasher automatiquement le champ "code" (PIN)
     */
    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = Hash::make($value);
    }

    /**
     * Vérifie si le compte est bloqué
     */
    public function isBlocked(): bool
    {
        return $this->blocked_until && $this->blocked_until->isFuture();
    }

    /**
     * Incrémente les tentatives de connexion
     */
    public function incrementLoginAttempts(): void
    {
        $this->login_attempts++;
        $attributes = ['last_login_attempt' => now()];

        if ($this->login_attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $attributes['blocked_until'] = now()->addMinutes(self::BLOCK_DURATION_MINUTES);
        }

        $this->update($attributes);
    }

    /**
     * Réinitialise les tentatives de connexion
     */
    public function resetLoginAttempts(): void
    {
        $this->update([
            'login_attempts' => 0,
            'blocked_until' => null,
            'last_login_attempt' => now(),
        ]);
    }

    /**
     * Nombre de tentatives restantes avant blocage
     */
    public function remainingAttempts(): int
    {
        return max(0, self::MAX_LOGIN_ATTEMPTS - $this->login_attempts);
    }

    /**
     * Temps restant de blocage en minutes
     */
    public function getBlockedTimeRemaining(): ?int
    {
        return $this->isBlocked()
            ? now()->diffInMinutes($this->blocked_until, false)
            : null;
    }

    /**
     * Crée un token d'accès personnel avec MongoDB
     */
    public function createToken(string $name = 'Personal Access Token', array $scopes = [])
    {
        $passportService = app(\App\Services\MongoPassportService::class);
        return $passportService->createPersonalAccessToken($this, $name, $scopes);
    }

    /**
     * Relations
     */

    // Un utilisateur possède un seul wallet
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    // Un utilisateur a plusieurs transactions via son wallet
    public function transactions()
    {
        return $this->hasManyThrough(Transaction::class, Wallet::class);
    }
}
