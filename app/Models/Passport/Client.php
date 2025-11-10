<?php

namespace App\Models\Passport;

use MongoDB\Laravel\Eloquent\Model as Moloquent;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Client extends Moloquent
{
    use HasUuids;

    protected $connection = 'mongodb';
    protected $collection = 'oauth_clients';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'secret',
        'provider',
        'redirect',
        'personal_access_client',
        'password_client',
        'revoked',
    ];

    protected $casts = [
        'personal_access_client' => 'boolean',
        'password_client' => 'boolean',
        'revoked' => 'boolean',
    ];
}
