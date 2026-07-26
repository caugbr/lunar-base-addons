<?php

namespace Plugins\Maps\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapMarker extends Model
{
    protected $table = 'map_markers';

    protected $fillable = [
        'map_id',
        'uid',
        'title',
        'content',
        'lat',
        'lng',
        'color',
        'icon',
        'parameters',
    ];

    protected $casts = [
        'lat'        => 'float',
        'lng'        => 'float',
        'parameters' => 'array',
    ];

    public function map(): BelongsTo
    {
        return $this->belongsTo(Map::class);
    }

    /**
     * Gera um UID curto (compatível com o WP: 'uid_' + 6 chars base36).
     */
    public static function generateUid(): string
    {
        return 'uid_' . substr(base_convert((string) mt_rand(), 10, 36), 0, 6);
    }
}
