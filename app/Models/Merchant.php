<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class Merchant extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'merchants';

    protected $fillable = ['name', 'code', 'telephone', 'email', 'status'];

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function transactions()
    {
        return $this->hasManyThrough(Transaction::class, Wallet::class);
    }
}
