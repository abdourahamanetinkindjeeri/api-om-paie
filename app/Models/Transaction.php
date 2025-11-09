<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use MongoDB\Laravel\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    // MongoDB connection
    protected $connection = 'mongodb';
    protected $collection = 'transactions';

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'status',
        'reference',
        'meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function logs()
    {
        return $this->hasMany(TransactionLog::class);
    }
}
