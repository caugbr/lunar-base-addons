<?php

namespace Plugins\Astrology\DTOs;

use Plugins\Astrology\Models\NatalChart;

class ChartLayerData
{
    public function __construct(
        public readonly string $label,
        public readonly string $subtitle,
        public readonly string $houseSystem,
        public readonly array $planets,
        public readonly array $angles,
        public readonly array $houses,
        public readonly ?string $timezone = null,
        public readonly ?int $sourceChartId = null,
    ) {
    }

    public static function fromNatalChart(NatalChart $chart, ?string $customLabel = null): self
    {
        $location = $chart->city ?: "{$chart->latitude}, {$chart->longitude}";
        $tz = $chart->timezone ? " ({$chart->timezone})" : '';

        return new self(
            label: $customLabel ?? $chart->name,
            subtitle: "{$chart->formatted_date_time}{$tz} — {$location}",
            houseSystem: $chart->house_system ?? 'placidus',
            planets: $chart->isCompleted() ? ($chart->planets ?? []) : [],
            angles: $chart->isCompleted() ? ($chart->angles ?? []) : [],
            houses: $chart->isCompleted() ? ($chart->houses ?? []) : [],
            timezone: $chart->timezone,
            sourceChartId: $chart->id,
        );
    }

    public static function fromCalculatedData(
        array $chartData,
        string $label,
        string $subtitle = '',
        string $houseSystem = 'placidus',
        ?string $timezone = null
    ): self {
        return new self(
            label: $label,
            subtitle: $subtitle,
            houseSystem: $houseSystem,
            planets: $chartData['planets'] ?? [],
            angles: $chartData['angles'] ?? [],
            houses: $chartData['houses'] ?? [],
            timezone: $timezone,
            sourceChartId: null,
        );
    }

    public function getAscendantLongitude(): float
    {
        if (isset($this->angles['Ascendant']['longitude'])) {
            return (float) $this->angles['Ascendant']['longitude'];
        }

        if (isset($this->houses[1]['longitude'])) {
            return (float) $this->houses[1]['longitude'];
        }

        return 0.0;
    }

    public function isReady(): bool
    {
        return !empty($this->planets);
    }
}
