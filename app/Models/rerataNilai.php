<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class rerataNilai extends Model
{
    use HasFactory;

    protected $table = 'rerata_nilai';

    protected $fillable = [
        'r_nilai1',
        'r_nilai2',
        'r_nilai3',
        'r_nilai4',
        'r_nilai5',
        'r_nilai6',
        'r_nilai7',
        'r_nilai8',
        'r_nilai9',
        'r_nilai10',
        'r_nilai11',
        'created_at',
        'updated_at',
    ];
}
