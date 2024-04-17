<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dataDesa extends Model
{
    use HasFactory;

    protected $table = 'tabel_desa';

    protected $fillable = [
        'nama_desa',
        'daerah_id', // tambahkan field daerah_id untuk merepresentasikan kunci asing ke tabel daerah
    ];
}
