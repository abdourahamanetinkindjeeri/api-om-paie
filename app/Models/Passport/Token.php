<?php

namespace App\Models\Passport;

use MongoDB\Laravel\Eloquent\Model as Moloquent;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Token extends Moloquent
{
    use HasUuids;

    protected $connection = 'mongodb';
    protected $collection = 'oauth_access_tokens';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'client_id',
        'name',
        'scopes',
        'revoked',
        'expires_at',
        'refresh_token_hash',
        'refresh_expires_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'revoked' => 'boolean',
        'expires_at' => 'datetime',
        'refresh_expires_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
