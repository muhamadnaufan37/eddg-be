<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class tblKlnderPndidikan extends Model
{
    use HasFactory;

    protected $table = 'kalender_pendidikan';

    protected $fillable = [
        'tahun_pelajaran',
        'semester_pelajaran',
        'status_pelajaran',
        'created_at',
    ];
}
