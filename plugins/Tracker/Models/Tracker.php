<?php

namespace Plugins\Tracker\Models;

use Illuminate\Database\Eloquent\Model;

class Tracker extends Model
{
    public $timestamps = false; // usamos apenas created_at gerenciado manualmente

    protected $table = 'tracker_page_views';

    protected $fillable = [
        'path',
        'referrer_host',
        'device',
        'browser',
        'country_code',
        'country_name',
        'region_name',
        'city_name',
        'visitor_hash',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
