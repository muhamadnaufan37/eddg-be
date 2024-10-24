<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletStatus extends Model
{
    use HasFactory;

    protected $table = 'wallet_status';

    protected $fillable = [
        'name_status',
    ];
}
