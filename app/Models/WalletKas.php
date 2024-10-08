<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletKas extends Model
{
    use HasFactory;

    protected $table = 'wallet_kas';

    protected $fillable = [
        'id_user',
        'jenis_transaksi',
        'tgl_transaksi',
        'keterangan',
        'jumlah',
        'created_at',
    ];
}
