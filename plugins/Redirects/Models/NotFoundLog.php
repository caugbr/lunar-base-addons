<?php

namespace Plugins\Redirects\Models;

use Illuminate\Database\Eloquent\Model;

class NotFoundLog extends Model
{
    protected $table = 'not_found_logs';
    protected $fillable = ['url', 'hits', 'referrer', 'last_accessed_at'];
    protected $casts = [
        'last_accessed_at' => 'datetime',
    ];
}
