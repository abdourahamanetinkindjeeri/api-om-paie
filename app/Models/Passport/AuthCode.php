<?php

namespace App\Models\Passport;

use Laravel\Passport\AuthCode as PassportAuthCode;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AuthCode extends PassportAuthCode
{
    use HasUuids;

    protected $connection = 'sqlite'; // Utilise la connexion SQLite
    protected $keyType = 'string';
    public $incrementing = false;
}
