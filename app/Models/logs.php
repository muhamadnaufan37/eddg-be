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
        'string_agent',
        'browser',
        'os',
        'device',
        'engine_agent',
        'continent_name',
        'country_code2',
        'country_code3',
        'country_name',
        'country_name_official',
        'state_prov',
        'district',
        'city',
        'zipcode',
        'latitude',
        'longitude',
        'isp',
        'connection_type',
        'organization',
        'timezone'
    ];
}
