<?php

namespace App\Models\Passport;

use Laravel\Passport\Token as PassportToken;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Token extends PassportToken
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
}
