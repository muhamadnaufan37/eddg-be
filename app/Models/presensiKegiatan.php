<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class presensiKegiatan extends Model
{
    use HasFactory;

    protected $table = 'presensi_kegiatan';

    protected $fillable = [
        'kode_kegiatan',
        'nama_kegiatan',
        'tmpt_kegiatan',
        'type_kegiatan',
        'tgl_kegiatan',
        'jam_kegiatan',
        'expired_date_time',
        'usia_batas',
        'usia_operator',
        'add_by_petugas',
        'tmpt_daerah',
        'tmpt_desa',
        'tmpt_kelompok',
    ];
}
