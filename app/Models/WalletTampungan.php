<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTampungan extends Model
{
    use HasFactory;

    protected $table = 'wallet_tampungan';

    protected $fillable = [
        'nama_tampungan',
    ];
}
