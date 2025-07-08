<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dataCai extends Model
{
    use HasFactory;

    protected $table = 'data_cai';

    protected $fillable = [
        'uuid',
        'kode_cari_data',
        'nama_lengkap',
        'jenis_kelamin',
        'status_utusan',
        'tmpt_daerah',
        'tmpt_desa',
        'tmpt_kelompok',
        'tahun',
        'img',
    ];

    protected static function boot()
    {
        parent::boot();

        // Otomatis buat UUID
        static::creating(function ($model) {
            $model->uuid = (string) \Illuminate\Support\Str::uuid();
        });
    }
}
