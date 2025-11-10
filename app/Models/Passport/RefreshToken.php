<?php

namespace App\Models\Passport;

use Laravel\Passport\RefreshToken as PassportRefreshToken;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RefreshToken extends PassportRefreshToken
{
    use HasUuids;

    protected $connection = 'sqlite'; // Utilise la connexion SQLite
    protected $keyType = 'string';
    public $incrementing = false;
}
