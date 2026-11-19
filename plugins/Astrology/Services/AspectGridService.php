<?php

namespace Plugins\Astrology\Services;

use Plugins\Astrology\DTOs\BiwheelChartData;
use Plugins\Astrology\DTOs\ChartLayerData;
use Plugins\Astrology\Models\NatalChart;

class AspectGridService
{
    protected array $canonicalOrder = [
        'Sun'        => ['label' => 'Sol', 'glyph' => '☉', 'file' => 'sun.svg'],
        'Moon'       => ['label' => 'Lua', 'glyph' => '☽', 'file' => 'moon.svg'],
        'Mercury'    => ['label' => 'Mercúrio', 'glyph' => '☿', 'file' => 'mercury.svg'],
        'Venus'      => ['label' => 'Vênus', 'glyph' => '♀', 'file' => 'venus.svg'],
        'Mars'       => ['label' => 'Marte', 'glyph' => '♂', 'file' => 'mars.svg'],
        'Jupiter'    => ['label' => 'Júpiter', 'glyph' => '♃', 'file' => 'jupiter.svg'],
        'Saturn'     => ['label' => 'Saturno', 'glyph' => '♄', 'file' => 'saturn.svg'],
        'Uranus'     => ['label' => 'Urano', 'glyph' => '♅', 'file' => 'uranus.svg'],
        'Neptune'    => ['label' => 'Netuno', 'glyph' => '♆', 'file' => 'neptune.svg'],
        'Pluto'      => ['label' => 'Plutão', 'glyph' => '♇', 'file' => 'pluto.svg'],
        'North Node' => ['label' => 'Nodo Norte', 'glyph' => '☊', 'file' => 'north_node.svg'],
        'Lilith'     => ['label' => 'Lilith', 'glyph' => '⚸', 'file' => 'lilith.svg'],
        'Chiron'     => ['label' => 'Quíron', 'glyph' => '⚷', 'file' => 'chiron.svg'],
        'Ascendant'  => ['label' => 'Ascendente', 'glyph' => 'AC', 'file' => null],
        'MC'         => ['label' => 'Meio do Céu', 'glyph' => 'MC', 'file' => null],
    ];

    protected array $signNames = [
        0 => 'Aries', 1 => 'Taurus', 2 => 'Gemini', 3 => 'Cancer',
        4 => 'Leo', 5 => 'Virgo', 6 => 'Libra', 7 => 'Scorpio',
        8 => 'Sagittarius', 9 => 'Capricorn', 10 => 'Aquarius', 11 => 'Pisces',
    ];

    public function buildGridData(NatalChart $chart, string $themeSlug = 'classic'): array
    {
        $theme = ThemeManager::getTheme($themeSlug);
        $themeDir = ThemeManager::getThemeDirectory($theme['slug']) ?? ($theme['path'] ?? '');

        $points = $this->extractPoints(
            $chart->planets,
            $chart->angles,
            $theme,
            $themeDir
        );

        $aspectMap = $this->indexAspects($chart->aspects, symmetric: true);

        return [
            'mode'      => 'single',
            'points'    => $points,
            'aspectMap' => $aspectMap,
        ];
    }

    public function buildCrossGridData(
        BiwheelChartData $data,
        string $themeSlug = 'classic',
        array $options = []
    ): array {
        $theme = ThemeManager::getBiwheelTheme($themeSlug);
        $themeDir = $theme['path'] ?? ThemeManager::getThemeDirectory($theme['slug'] ?? $themeSlug) ?? '';

        $includeInnerAngles = (bool) ($options['include_inner_angles'] ?? true);
        $includeOuterAngles = (bool) ($options['include_outer_angles'] ?? true);

        $rowPoints = $this->extractPoints(
            $data->inner->planets,
            $data->inner->angles,
            $theme,
            $themeDir,
            includeAngles: $includeInnerAngles
        );

        $colPoints = $this->extractPoints(
            $data->outer->planets,
            $data->outer->angles,
            $theme,
            $themeDir,
            houseOverrides: $data->houseOverlays,
            includeAngles: $includeOuterAngles
        );

        // Direcional: $aspectMap[$innerKey][$outerKey]
        $aspectMap = $this->indexAspects($data->crossAspects, symmetric: false, isCross: true);

        return [
            'mode'       => 'biwheel',
            'innerLabel' => $data->inner->label,
            'outerLabel' => $data->outer->label,
            'rowPoints'  => $rowPoints,
            'colPoints'  => $colPoints,
            'aspectMap'  => $aspectMap,
        ];
    }

    public function resolveGridData(
        NatalChart|ChartLayerData|null $chart = null,
        ?BiwheelChartData $biwheel = null,
        NatalChart|ChartLayerData|null $outer = null,
        array $crossAspects = [],
        array $houseOverlays = [],
        string $themeSlug = 'classic',
        array $options = []
    ): array {
        if ($biwheel instanceof BiwheelChartData) {
            return $this->buildCrossGridData($biwheel, $themeSlug, $options);
        }

        if ($chart !== null && $outer !== null) {
            $innerLayer = $chart instanceof ChartLayerData
                ? $chart
                : ChartLayerData::fromNatalChart($chart);

            $outerLayer = $outer instanceof ChartLayerData
                ? $outer
                : ChartLayerData::fromNatalChart($outer);

            $biwheelData = new BiwheelChartData(
                inner: $innerLayer,
                outer: $outerLayer,
                crossAspects: $crossAspects,
                houseOverlays: $houseOverlays,
            );

            return $this->buildCrossGridData($biwheelData, $themeSlug, $options);
        }

        if ($chart instanceof NatalChart) {
            return $this->buildGridData($chart, $themeSlug);
        }

        return [
            'mode'      => 'single',
            'points'    => [],
            'aspectMap' => [],
        ];
    }

    protected function extractPoints(
        array $planets,
        array $angles,
        array $theme,
        string $themeDir,
        array $houseOverrides = [],
        bool $includeAngles = true
    ): array {
        $angleKeys = ['Ascendant', 'MC'];
        $points = [];

        foreach ($this->canonicalOrder as $key => $meta) {
            if (!$includeAngles && in_array($key, $angleKeys, true)) {
                continue;
            }

            $data = $planets[$key] ?? ($includeAngles ? ($angles[$key] ?? null) : null);
            if (!$data) {
                continue;
            }

            $iconDataUri = null;
            if (!empty($meta['file'])) {
                $iconPath = $themeDir . '/' . ($theme['assets']['planetsDir'] ?? 'planets/') . $meta['file'];
                $iconDataUri = ChartSvgBuilder::fileToDataUri($iconPath);
            }

            $formatted = $data['formatted'] ?? '';
            if ($formatted === '' && isset($data['longitude'])) {
                $formatted = $this->formatLongitude((float) $data['longitude']);
            }

            $points[] = [
                'key'        => $key,
                'label'      => $meta['label'],
                'glyph'      => $meta['glyph'],
                'iconUri'    => $iconDataUri,
                'formatted'  => $formatted,
                'house'      => $houseOverrides[$key] ?? ($data['house'] ?? null),
                'retrograde' => !empty($data['is_retrograde']),
            ];
        }

        return $points;
    }

    protected function indexAspects(array $aspects, bool $symmetric = true, bool $isCross = false): array
    {
        $aspectMap = [];

        foreach ($aspects as $asp) {
            $p1 = $asp['point1'] ?? '';
            $p2 = $asp['point2'] ?? '';
            if (!$p1 || !$p2) {
                continue;
            }

            $stateIndicator = '';
            if (!empty($asp['is_applying'])) {
                $stateIndicator = 'a';
            } elseif (isset($asp['is_applying']) && !$asp['is_applying']) {
                $stateIndicator = 's';
            }

            $p1Pt = AstrologyTranslator::translate($p1);
            $aspPt = AstrologyTranslator::translate($asp['aspect'] ?? '');
            $p2Pt = AstrologyTranslator::translate($p2);
            $orbFormatted = $asp['orb_formatted'] ?? '';

            $title = $isCross
                ? "Interno {$p1Pt} {$aspPt} Externo {$p2Pt} ({$orbFormatted})"
                : "{$p1Pt} {$aspPt} {$p2Pt} ({$orbFormatted})";

            $data = [
                'aspect'   => $asp['aspect'] ?? '',
                'type'     => $asp['aspect_type'] ?? '',
                'symbol'   => $asp['symbol'] ?? '',
                'orb'      => $orbFormatted,
                'state'    => $stateIndicator,
                'is_minor' => ($asp['category'] ?? '') === 'minor',
                'title'    => $title,
            ];

            $aspectMap[$p1][$p2] = $data;
            if ($symmetric) {
                $aspectMap[$p2][$p1] = $data;
            }
        }

        return $aspectMap;
    }

    protected function formatLongitude(float $longitude): string
    {
        $lon = fmod(fmod($longitude, 360.0) + 360.0, 360.0);
        $signIdx = (int) floor($lon / 30.0);
        $degInSign = fmod($lon, 30.0);
        $d = (int) floor($degInSign);
        $m = (int) round(($degInSign - $d) * 60);

        if ($m >= 60) {
            $d++;
            $m = 0;
        }

        $sign = $this->signNames[$signIdx] ?? 'Aries';

        return sprintf("%02d° %s %02d'", $d, $sign, $m);
    }
}
