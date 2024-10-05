<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTypePayment extends Model
{
    use HasFactory;

    protected $table = 'wallet_type_payment';

    protected $fillable = [
        'channel_name_payment',
        'string_name_payment',
        'info_status_payment',
    ];
}
