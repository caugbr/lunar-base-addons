<?php

namespace Plugins\Cities\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'cities';

    protected $fillable = [
        'city',
        'city_ascii',
        'lat',
        'lng',
        'country',
        'iso2',
        'iso3',
        'timezone',
        'admin_name',
        'capital',
        'population',
    ];

    protected $casts = [
        'lat'        => 'float',
        'lng'        => 'float',
        'population' => 'integer',
    ];
}
