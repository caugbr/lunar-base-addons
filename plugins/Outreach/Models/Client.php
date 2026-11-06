<?php

namespace Plugins\Outreach\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $table = 'outreach_clients';

    protected $fillable = [
        'name', 'email', 'company', 'role', 'phone',
        'website', 'status', 'tags', 'custom_fields', 'last_contacted_at'
    ];

    protected $casts = [
        'tags'              => 'array',
        'custom_fields'     => 'array',
        'last_contacted_at' => 'datetime',
    ];

    public function notes(): HasMany
    {
        return $this->hasMany(ClientNote::class, 'client_id')->latest();
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'client_id')->latest();
    }

    /**
     * Extrai apenas o primeiro nome para uso no e-mail
     */
    public function getFirstNameAttribute(): string
    {
        return explode(' ', trim($this->name))[0];
    }
}
