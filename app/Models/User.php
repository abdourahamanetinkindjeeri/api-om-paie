<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids,HasApiTokens;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'nom',
        'prenom',
        'type_piece',
        'numero',
        'adresse',
        'code', // utilisé comme mot de passe
        'telephone',
        'email',
        'email_verified_at',
    ];

    protected $hidden = [
        'code', // masqué dans les réponses JSON
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Mutateur pour hasher automatiquement le champ "code"
     */
    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = Hash::make($value);
    }
}
