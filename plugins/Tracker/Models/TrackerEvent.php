<?php

namespace Plugins\Tracker\Models;

use Illuminate\Database\Eloquent\Model;

class TrackerEvent extends Model
{
    public $timestamps = false;

    protected $table = 'tracker_events';

    protected $fillable = [
        'event_name',
        'event_category',
        'path',
        'visitor_hash',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
