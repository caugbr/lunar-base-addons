<?php

namespace Plugins\ArchiveVault\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivedRecord extends Model
{
    protected $table = 'archived_records';

    protected $fillable = [
        'archivable_type',
        'original_id',
        'title',
        'slug',
        'payload',
        'original_created_at',
        'purged_at'
    ];

    protected $casts = [
        'payload'             => 'array',
        'original_created_at' => 'datetime',
        'purged_at'           => 'datetime',
    ];

    public function getTypeNameAttribute(): string
    {
        return str_contains($this->archivable_type, 'Post') ? 'Post' : 'Página';
    }
}
