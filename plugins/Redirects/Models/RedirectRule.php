<?php

namespace Plugins\Redirects\Models;

use Illuminate\Database\Eloquent\Model;

class RedirectRule extends Model
{
    protected $table = 'redirect_rules';
    protected $fillable = ['old_url', 'new_url', 'status_code'];
}
