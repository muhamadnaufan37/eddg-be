<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dataDaerah extends Model
{
    use HasFactory;

    protected $table = 'tabel_daerah';

    protected $fillable = [
        'nama_daerah',
    ];
}
