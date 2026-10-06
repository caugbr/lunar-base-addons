<?php

namespace Plugins\Heatmap\Models;

use Illuminate\Database\Eloquent\Model;

class Heatmap extends Model
{
    protected $table = 'heatmap_clicks';

    public $timestamps = false;

    protected $fillable = [
        'url_path',
        'selector',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
