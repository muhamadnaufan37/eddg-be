<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class presensi extends Model
{
    use HasFactory;

    protected $table = 'presensi';

    protected $fillable = [
        'id_kegiatan',
        'id_peserta',
        'id_petugas',
        'status_presensi',
        'keterangan',
        'waktu_presensi',
    ];
}
