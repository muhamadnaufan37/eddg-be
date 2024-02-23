<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boarcast extends Model
{
    use HasFactory;

    protected $table = 'broadcast';

    protected $fillable = [
        'id_user',
        'judul_broadcast',
        'jenis_broadcast',
        'text_broadcast',
        'ip',
    ];
}
