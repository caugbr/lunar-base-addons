<?php

namespace Plugins\Astrology\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NatalChart extends Model
{
    protected $table = 'natal_charts';

    protected $fillable = [
        'name',
        'slug',
        'birth_date',
        'birth_time',
        'timezone',
        'city',
        'latitude',
        'longitude',
        'house_system',
        'include_minors',
        'orb_general',
        'orb_luminaries',
        'status',
        'error_message',
        'sun_sign',
        'moon_sign',
        'ascendant_sign',
        'chart_data',
        'is_public',
        'created_by',
    ];

    protected $casts = [
        'birth_date'     => 'date',
        'latitude'       => 'float',
        'longitude'      => 'float',
        'include_minors' => 'boolean',
        'orb_general'    => 'float',
        'orb_luminaries' => 'float',
        'is_public'      => 'boolean',
        'chart_data'     => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function getPlanetsAttribute(): array
    {
        return $this->chart_data['planets'] ?? [];
    }

    public function getAnglesAttribute(): array
    {
        return $this->chart_data['angles'] ?? [];
    }

    public function getHousesAttribute(): array
    {
        return $this->chart_data['houses'] ?? [];
    }

    public function getAspectsAttribute(): array
    {
        return $this->chart_data['aspects'] ?? [];
    }

    public function getFormattedDateTimeAttribute(): string
    {
        $date = $this->birth_date ? $this->birth_date->format('d/m/Y') : '—';
        $time = substr($this->birth_time ?? '', 0, 5);
        return "{$date} às {$time}";
    }

    public function getRenderedSvg(string $theme = 'classic'): string
    {
        $cacheKey = "natal_chart_svg_{$this->id}_{$theme}";

        return cache()->rememberForever($cacheKey, function () use ($theme) {
            return app(\Plugins\Astrology\Services\ChartSvgBuilder::class)->build($this, $theme);
        });
    }

    public function forgetRenderedSvgCache(?string $theme = null): void
    {
        if ($theme) {
            cache()->forget("natal_chart_svg_{$this->id}_{$theme}");
            return;
        }

        $availableThemes = \Plugins\Astrology\Services\ThemeManager::getThemes();
        foreach (array_keys($availableThemes) as $themeSlug) {
            cache()->forget("natal_chart_svg_{$this->id}_{$themeSlug}");
        }
    }
}
