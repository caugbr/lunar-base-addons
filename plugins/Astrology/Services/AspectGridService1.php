<?php

namespace Plugins\Astrology\Services;

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
        'MC'  => ['label' => 'Meio do Céu', 'glyph' => 'MC', 'file' => null],
    ];

    public function buildGridData(NatalChart $chart, string $themeSlug = 'classic'): array
    {
        $theme = ThemeManager::getTheme($themeSlug);
        $themeDir = ThemeManager::getThemeDirectory($theme['slug']) ?? ($theme['path'] ?? '');

        $planets = $chart->planets;
        $angles = $chart->angles;
        $aspects = $chart->aspects;

        $points = [];
        foreach ($this->canonicalOrder as $key => $meta) {
            $data = $planets[$key] ?? $angles[$key] ?? null;
            if (!$data) {
                continue;
            }

            $iconDataUri = null;
            if ($meta['file']) {
                $iconPath = $themeDir . '/' . ($theme['assets']['planetsDir'] ?? 'planets/') . $meta['file'];
                $iconDataUri = ChartSvgBuilder::fileToDataUri($iconPath);
            }

            $points[] = [
                'key'         => $key,
                'label'       => $meta['label'],
                'glyph'       => $meta['glyph'],
                'iconUri'     => $iconDataUri,
                'formatted'   => $data['formatted'] ?? '',
                'house'       => $data['house'] ?? null,
                'retrograde'  => !empty($data['is_retrograde']),
            ];
        }

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

            $data = [
                'aspect'       => $asp['aspect'] ?? '',
                'type'         => $asp['aspect_type'] ?? '',
                'symbol'       => $asp['symbol'] ?? '',
                'orb'          => $asp['orb_formatted'] ?? '',
                'state'        => $stateIndicator,
                'is_minor'     => ($asp['category'] ?? '') === 'minor',
                'title'        => "{$p1Pt} {$aspPt} {$p2Pt} ({$asp['orb_formatted']})",
            ];

            $aspectMap[$p1][$p2] = $data;
            $aspectMap[$p2][$p1] = $data;
        }

        return [
            'points'    => $points,
            'aspectMap' => $aspectMap,
        ];
    }
}
