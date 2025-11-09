<?php

namespace App\Models\Passport;

use Laravel\Passport\Client as PassportClient;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Client extends PassportClient
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
}
