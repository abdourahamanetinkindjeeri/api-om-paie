<?php

namespace App\Models\Passport;

use Laravel\Passport\PersonalAccessClient as PassportPersonalAccessClient;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PersonalAccessClient extends PassportPersonalAccessClient
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
}
