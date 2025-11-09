<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

class TransactionLog extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';
    protected $collection = 'transaction_logs';

    protected $fillable = ['transaction_id', 'action', 'description'];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
