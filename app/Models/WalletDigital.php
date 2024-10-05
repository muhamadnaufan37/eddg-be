<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletDigital extends Model
{
    use HasFactory;

    protected $table = 'wallet_kas_digital';

    protected $fillable = [
        'transaction_id',
        'order_id',
        'wallet_user_id',
        'wallet_sensus_id',
        'bulan',
        'jenis_tampungan',
        'payment_type',
        'keterangan',
        'transaction_status',
        'amount',
    ];
}
