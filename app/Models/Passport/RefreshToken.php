<?php

namespace App\Models\Passport;

use Laravel\Passport\RefreshToken as PassportRefreshToken;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RefreshToken extends PassportRefreshToken
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
}
