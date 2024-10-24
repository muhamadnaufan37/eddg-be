<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dataKelompok extends Model
{
    use HasFactory;

    protected $table = 'tabel_kelompok';

    protected $fillable = [
        'nama_kelompok',
        'desa_id', // tambahkan field daerah_id untuk merepresentasikan kunci asing ke tabel desa
    ];
}
