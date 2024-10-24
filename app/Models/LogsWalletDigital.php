<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogsWalletDigital extends Model
{
    use HasFactory;

    protected $table = 'logs_wallet_digital';

    protected $fillable = [
        'transaction_id',
        'payment_type',
        'total_amount',
        'status_bayar',
        'created_at',
    ];
}
