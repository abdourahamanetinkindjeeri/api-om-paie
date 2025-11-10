<?php

namespace App\Models\Passport;

use MongoDB\Laravel\Eloquent\Model as Moloquent;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PersonalAccessClient extends Moloquent
{
    use HasUuids;

    protected $connection = 'mongodb';
    protected $collection = 'oauth_personal_access_clients';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
