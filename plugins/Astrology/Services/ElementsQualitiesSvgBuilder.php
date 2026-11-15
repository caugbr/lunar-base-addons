<?php

namespace Plugins\Astrology\Services;

use Plugins\Astrology\Models\NatalChart;

class ElementsQualitiesSvgBuilder
{
    protected array $participatingPoints = [
        'Sun'       => ['label' => 'Sol', 'glyph' => '☉', 'file' => 'sun.svg'],
        'Moon'      => ['label' => 'Lua', 'glyph' => '☽', 'file' => 'moon.svg'],
        'Mercury'   => ['label' => 'Mercúrio', 'glyph' => '☿', 'file' => 'mercury.svg'],
        'Venus'     => ['label' => 'Vênus', 'glyph' => '♀', 'file' => 'venus.svg'],
        'Mars'      => ['label' => 'Marte', 'glyph' => '♂', 'file' => 'mars.svg'],
        'Jupiter'   => ['label' => 'Júpiter', 'glyph' => '♃', 'file' => 'jupiter.svg'],
        'Saturn'    => ['label' => 'Saturno', 'glyph' => '♄', 'file' => 'saturn.svg'],
        'Uranus'    => ['label' => 'Urano', 'glyph' => '♅', 'file' => 'uranus.svg'],
        'Neptune'   => ['label' => 'Netuno', 'glyph' => '♆', 'file' => 'neptune.svg'],
        'Pluto'     => ['label' => 'Plutão', 'glyph' => '♇', 'file' => 'pluto.svg'],
        'Ascendant' => ['label' => 'Ascendente', 'glyph' => 'AC', 'file' => null],
        'MC'        => ['label' => 'Meio do Céu', 'glyph' => 'MC', 'file' => null],
    ];

    protected array $elements = [
        0 => ['id' => 'fire',  'label' => 'FOGO', 'abbr' => 'FOG'],
        1 => ['id' => 'earth', 'label' => 'TERRA', 'abbr' => 'TER'],
        2 => ['id' => 'air',   'label' => 'AR', 'abbr' => 'AR'],
        3 => ['id' => 'water', 'label' => 'ÁGUA', 'abbr' => 'ÁGU'],
    ];

    protected array $qualities = [
        0 => ['id' => 'cardinal', 'label' => 'CARDINAL', 'abbr' => 'CAR'],
        1 => ['id' => 'fixed',    'label' => 'FIXO', 'abbr' => 'FIX'],
        2 => ['id' => 'mutable',  'label' => 'MUTÁVEL', 'abbr' => 'MUT'],
    ];

    public function build(NatalChart $chart, array $theme, float $originX, float $originY): string
    {
        $distribution = $this->calculateDistribution($chart, $theme);

        $rowLabelWidth = 65.0;
        $colWidth = 85.0;
        $headerHeight = 26.0;
        $rowHeight = 32.0;

        $totalWidth = $rowLabelWidth + (3 * $colWidth);
        $totalHeight = $headerHeight + (4 * $rowHeight);

        $borderColor = $theme['houses']['color'] ?? '#94a3b8';
        $borderWidth = 1.0;
        $textColor = '#334155';
        $mutedTextColor = '#64748b';

        $svg = "  <g class=\"chart-elements-qualities\" transform=\"translate({$originX}, {$originY})\">\n";

        // Borda externa da tabela
        $svg .= "    <rect x=\"0\" y=\"0\" width=\"{$totalWidth}\" height=\"{$totalHeight}\" "
             . "fill=\"none\" stroke=\"{$borderColor}\" stroke-width=\"{$borderWidth}\" />\n";

        // Linha divisória horizontal do cabeçalho
        $svg .= "    <line x1=\"0\" y1=\"{$headerHeight}\" x2=\"{$totalWidth}\" y2=\"{$headerHeight}\" "
             . "stroke=\"{$borderColor}\" stroke-width=\"{$borderWidth}\" />\n";

        // Linha divisória vertical da coluna de rótulos dos elementos
        $svg .= "    <line x1=\"{$rowLabelWidth}\" y1=\"0\" x2=\"{$rowLabelWidth}\" y2=\"{$totalHeight}\" "
             . "stroke=\"{$borderColor}\" stroke-width=\"{$borderWidth}\" />\n";

        // Cabeçalhos de coluna (Modalidades + Totais)
        for ($c = 0; $c < 3; $c++) {
            $colX = $rowLabelWidth + ($c * $colWidth);

            if ($c > 0) {
                $svg .= "    <line x1=\"{$colX}\" y1=\"0\" x2=\"{$colX}\" y2=\"{$totalHeight}\" "
                     . "stroke=\"{$borderColor}\" stroke-width=\"{$borderWidth}\" />\n";
            }

            $midX = $colX + ($colWidth / 2.0);
            $midY = $headerHeight / 2.0;
            $qualityMeta = $this->qualities[$c];
            $count = $distribution['qualityTotals'][$c] ?? 0;
            $headerText = htmlspecialchars("{$qualityMeta['abbr']} {$count}", ENT_QUOTES | ENT_XML1, 'UTF-8');

            $svg .= "    <text x=\"{$midX}\" y=\"{$midY}\" font-size=\"10\" font-weight=\"bold\" "
                 . "font-family=\"sans-serif\" fill=\"{$textColor}\" text-anchor=\"middle\" dominant-baseline=\"central\">\n";
            $svg .= "      {$headerText}\n";
            $svg .= "    </text>\n";
        }

        // Linhas de dados (Elementos + Totais + Células)
        for ($r = 0; $r < 4; $r++) {
            $rowY = $headerHeight + ($r * $rowHeight);

            if ($r > 0) {
                $svg .= "    <line x1=\"0\" y1=\"{$rowY}\" x2=\"{$totalWidth}\" y2=\"{$rowY}\" "
                     . "stroke=\"{$borderColor}\" stroke-width=\"{$borderWidth}\" />\n";
            }

            // Rótulo do elemento na primeira coluna
            $elementMeta = $this->elements[$r];
            $rowCount = $distribution['elementTotals'][$r] ?? 0;
            $rowLabelText = htmlspecialchars("{$elementMeta['abbr']} {$rowCount}", ENT_QUOTES | ENT_XML1, 'UTF-8');
            $labelMidY = $rowY + ($rowHeight / 2.0);

            $svg .= "    <text x=\"10\" y=\"{$labelMidY}\" font-size=\"10\" font-weight=\"bold\" "
                 . "font-family=\"sans-serif\" fill=\"{$textColor}\" text-anchor=\"start\" dominant-baseline=\"central\">\n";
            $svg .= "      {$rowLabelText}\n";
            $svg .= "    </text>\n";

            // Células com os corpos celestes
            for ($c = 0; $c < 3; $c++) {
                $cellX = $rowLabelWidth + ($c * $colWidth);
                $cellPoints = $distribution['matrix'][$r][$c] ?? [];
                $pointCount = count($cellPoints);

                if ($pointCount === 0) {
                    continue;
                }

                $iconSize = 16.0;
                $spacing = 3.0;
                $itemsTotalWidth = ($pointCount * $iconSize) + (($pointCount - 1) * $spacing);

                // Centraliza os ícones horizontalmente dentro da célula
                $startX = $cellX + max(6.0, ($colWidth - $itemsTotalWidth) / 2.0);
                $itemCenterY = $rowY + ($rowHeight / 2.0);

                foreach ($cellPoints as $idx => $pt) {
                    $itemX = $startX + ($idx * ($iconSize + $spacing));
                    $titleText = htmlspecialchars("{$pt['label']} em {$pt['signName']}", ENT_QUOTES | ENT_XML1, 'UTF-8');

                    $svg .= "    <g transform=\"translate({$itemX}, " . ($itemCenterY - ($iconSize / 2.0)) . ")\">\n";
                    $svg .= "      <title>{$titleText}</title>\n";

                    if (!empty($pt['iconUri'])) {
                        $svg .= "      <image href=\"{$pt['iconUri']}\" x=\"0\" y=\"0\" width=\"{$iconSize}\" height=\"{$iconSize}\" />\n";
                    } else {
                        $escapedGlyph = htmlspecialchars($pt['glyph'], ENT_QUOTES | ENT_XML1, 'UTF-8');
                        $svg .= "      <text x=\"" . ($iconSize / 2.0) . "\" y=\"" . ($iconSize / 2.0) . "\" font-size=\"10\" font-weight=\"bold\" "
                             . "font-family=\"sans-serif\" fill=\"{$textColor}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$escapedGlyph}</text>\n";
                    }

                    $svg .= "    </g>\n";
                }
            }
        }

        $svg .= "  </g>\n";

        return $svg;
    }

    protected function calculateDistribution(NatalChart $chart, array $theme): array
    {
        $themeSlug = $theme['slug'] ?? 'classic';
        $themeDir = ThemeManager::getThemeDirectory($themeSlug);
        $ext = $theme['assets']['extension'] ?? 'svg';

        $planets = $chart->planets;
        $angles = $chart->angles;

        $matrix = [
            0 => [0 => [], 1 => [], 2 => []],
            1 => [0 => [], 1 => [], 2 => []],
            2 => [0 => [], 1 => [], 2 => []],
            3 => [0 => [], 1 => [], 2 => []],
        ];

        $elementTotals = [0 => 0, 1 => 0, 2 => 0, 3 => 0];
        $qualityTotals = [0 => 0, 1 => 0, 2 => 0];

        $signNames = [
            0 => 'Áries', 1 => 'Touro', 2 => 'Gêmeos', 3 => 'Câncer',
            4 => 'Leão', 5 => 'Virgem', 6 => 'Libra', 7 => 'Escorpião',
            8 => 'Sagitário', 9 => 'Capricórnio', 10 => 'Aquário', 11 => 'Peixes'
        ];

        foreach ($this->participatingPoints as $key => $meta) {
            $data = $planets[$key] ?? $angles[$key] ?? null;
            if (!$data || !isset($data['longitude'])) {
                continue;
            }

            $longitude = (float) $data['longitude'];
            $signIndex = (int) floor(fmod($longitude, 360.0) / 30.0);

            $elementIdx = $signIndex % 4;
            $qualityIdx = $signIndex % 3;

            $iconUri = null;
            if (!empty($meta['file'])) {
                $iconPath = $themeDir . '/' . ($theme['assets']['planetsDir'] ?? 'planets/') . $meta['file'];
                if (file_exists($iconPath)) {
                    $iconUri = ChartSvgBuilder::fileToDataUri($iconPath);
                }
            }

            $pointData = [
                'key'      => $key,
                'label'    => $meta['label'],
                'glyph'    => $meta['glyph'],
                'iconUri'  => $iconUri,
                'signName' => $signNames[$signIndex] ?? '',
            ];

            $matrix[$elementIdx][$qualityIdx][] = $pointData;
            $elementTotals[$elementIdx]++;
            $qualityTotals[$qualityIdx]++;
        }

        return [
            'matrix'        => $matrix,
            'elementTotals' => $elementTotals,
            'qualityTotals' => $qualityTotals,
        ];
    }
}
