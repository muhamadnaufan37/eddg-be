<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class logs extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'aktifitas',
        'status_logs',
        'browser',
        'os',
        'device',
        'latitude',
        'longitude'
    ];
}
