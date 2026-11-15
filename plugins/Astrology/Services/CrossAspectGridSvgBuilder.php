<?php

namespace Plugins\Astrology\Services;

use Plugins\Astrology\DTOs\BiwheelChartData;
use Plugins\Astrology\DTOs\ChartLayerData;

class CrossAspectGridSvgBuilder
{
    protected array $participatingPoints = [
        'Sun' => ['label' => 'Sol', 'glyph' => '☉', 'file' => 'sun.svg'],
        'Moon' => ['label' => 'Lua', 'glyph' => '☽', 'file' => 'moon.svg'],
        'Mercury' => ['label' => 'Mercúrio', 'glyph' => '☿', 'file' => 'mercury.svg'],
        'Venus' => ['label' => 'Vênus', 'glyph' => '♀', 'file' => 'venus.svg'],
        'Mars' => ['label' => 'Marte', 'glyph' => '♂', 'file' => 'mars.svg'],
        'Jupiter' => ['label' => 'Júpiter', 'glyph' => '♃', 'file' => 'jupiter.svg'],
        'Saturn' => ['label' => 'Saturno', 'glyph' => '♄', 'file' => 'saturn.svg'],
        'Uranus' => ['label' => 'Urano', 'glyph' => '♅', 'file' => 'uranus.svg'],
        'Neptune' => ['label' => 'Netuno', 'glyph' => '♆', 'file' => 'neptune.svg'],
        'Pluto' => ['label' => 'Plutão', 'glyph' => '♇', 'file' => 'pluto.svg'],
        'North Node' => ['label' => 'Nodo N.', 'glyph' => '☊', 'file' => 'north_node.svg'],
        'Lilith' => ['label' => 'Lilith', 'glyph' => '⚸', 'file' => 'lilith.svg'],
        'Chiron' => ['label' => 'Quíron', 'glyph' => '⚷', 'file' => 'chiron.svg'],
        'Ascendant' => ['label' => 'Ascendente', 'glyph' => 'AC', 'file' => null],
        'MC' => ['label' => 'Meio do Céu', 'glyph' => 'MC', 'file' => null],
    ];

    protected array $signNames = [
        0 => 'Aries', 1 => 'Taurus', 2 => 'Gemini', 3 => 'Cancer',
        4 => 'Leo', 5 => 'Virgo', 6 => 'Libra', 7 => 'Scorpio',
        8 => 'Sagittarius', 9 => 'Capricorn', 10 => 'Aquarius', 11 => 'Pisces',
    ];

    protected array $signUnicodes = [
        0 => '♈', 1 => '♉', 2 => '♊', 3 => '♋',
        4 => '♌', 5 => '♍', 6 => '♎', 7 => '♏',
        8 => '♐', 9 => '♑', 10 => '♒', 11 => '♓',
    ];

    public function build(
        BiwheelChartData $data,
        array $theme,
        float $originX = 50.0,
        float $originY = 885.0,
        float $cellSize = 28.0,
        float $summaryWidth = 165.0
    ): string {
        $themeSlug = $theme['slug'] ?? 'classic';
        $themeDir = $theme['path'] ?? ThemeManager::getThemeDirectory($themeSlug) ?? '';
        $ext = $theme['assets']['extension'] ?? 'svg';

        $signIconUris = [];
        $innerPoints = $this->extractPoints($data->inner, $theme, $themeDir, $ext, $signIconUris);
        $outerPoints = $this->extractPoints($data->outer, $theme, $themeDir, $ext, $signIconUris, $data->houseOverlays);

        $rowCount = count($innerPoints);
        $colCount = count($outerPoints);

        if ($rowCount === 0 || $colCount === 0) {
            return '';
        }

        // Garante que a matriz + painel lateral direito caibam nos 900 px uteis da prancha A4
        $maxMatrixWidth = 605.0;
        $computedCellSize = min($cellSize, floor(($maxMatrixWidth - $summaryWidth) / $colCount));
        $computedCellSize = max(21.0, $computedCellSize);

        $maxMatrixHeight = 460.0;
        $headerHeight = 40.0;
        $rowHeight = min($computedCellSize, floor(($maxMatrixHeight - $headerHeight) / $rowCount));
        $rowHeight = max(20.0, $rowHeight);

        $aspectMap = $this->indexAspects($data->crossAspects, $theme);

        $borderColor = $theme['houses']['color'] ?? '#94a3b8';
        $borderWidth = 0.75;
        $textColor = '#334155';
        $mutedColor = '#64748b';

        $svg = "  <g class=\"chart-cross-aspect-grid\" transform=\"translate({$originX}, {$originY})\">\n";

        // Celula de canto superior esquerdo (legenda dos eixos)
        $svg .= "    <rect x=\"0\" y=\"0\" width=\"{$summaryWidth}\" height=\"{$headerHeight}\" fill=\"#f8fafc\" stroke=\"{$borderColor}\" stroke-width=\"{$borderWidth}\" />\n";
        $svg .= "    <line x1=\"0\" y1=\"0\" x2=\"{$summaryWidth}\" y2=\"{$headerHeight}\" stroke=\"{$borderColor}\" stroke-width=\"0.5\" stroke-opacity=\"0.5\" />\n";
        $svg .= "    <text x=\"" . ($summaryWidth - 6) . "\" y=\"13\" font-size=\"8\" font-weight=\"bold\" font-family=\"sans-serif\" fill=\"#db2777\" text-anchor=\"end\">EXTERNO (colunas)</text>\n";
        $svg .= "    <text x=\"6\" y=\"" . ($headerHeight - 8) . "\" font-size=\"8\" font-weight=\"bold\" font-family=\"sans-serif\" fill=\"#0284c7\" text-anchor=\"start\">INTERNO (linhas)</text>\n";

        // Cabecalho das colunas (mapa externo)
        for ($j = 0; $j < $colCount; $j++) {
            $colPt = $outerPoints[$j];
            $colX = $summaryWidth + ($j * $computedCellSize);
            $colMidX = $colX + ($computedCellSize / 2.0);
            $iconSize = 14.0;

            $svg .= "    <rect x=\"{$colX}\" y=\"0\" width=\"{$computedCellSize}\" height=\"{$headerHeight}\" fill=\"#fdf2f8\" stroke=\"{$borderColor}\" stroke-width=\"{$borderWidth}\" />\n";

            $signPt = AstrologyTranslator::translate($colPt['signName']);
            $colTitle = htmlspecialchars(
                "Externo {$colPt['label']}: {$colPt['deg']} {$signPt} {$colPt['min']}"
                . ($colPt['house'] ? " (Casa {$colPt['house']} interna)" : ''),
                ENT_QUOTES | ENT_XML1,
                'UTF-8'
            );

            $svg .= "    <g>\n";
            $svg .= "      <title>{$colTitle}</title>\n";
            if (!empty($colPt['iconUri'])) {
                $svg .= "      <image href=\"{$colPt['iconUri']}\" x=\"" . ($colMidX - ($iconSize / 2.0)) . "\" y=\"5\" width=\"{$iconSize}\" height=\"{$iconSize}\" />\n";
            } else {
                $escapedGlyph = htmlspecialchars($colPt['glyph'], ENT_QUOTES | ENT_XML1, 'UTF-8');
                $svg .= "      <text x=\"{$colMidX}\" y=\"13\" font-size=\"9.5\" font-weight=\"bold\" font-family=\"sans-serif\" fill=\"#db2777\" text-anchor=\"middle\" dominant-baseline=\"central\">{$escapedGlyph}</text>\n";
            }

            $houseHint = $colPt['house'] ? "C{$colPt['house']}" : $colPt['deg'];
            $svg .= "      <text x=\"{$colMidX}\" y=\"30\" font-size=\"7.5\" font-family=\"sans-serif\" fill=\"{$mutedColor}\" text-anchor=\"middle\" dominant-baseline=\"central\">"
                 . htmlspecialchars($houseHint, ENT_QUOTES | ENT_XML1, 'UTF-8')
                 . "</text>\n";
            $svg .= "    </g>\n";
        }

        // Linhas (mapa interno) + celulas de cruzamento
        for ($i = 0; $i < $rowCount; $i++) {
            $rowPt = $innerPoints[$i];
            $rowY = $headerHeight + ($i * $rowHeight);
            $midY = $rowY + ($rowHeight / 2.0);

            $svg .= $this->renderPointSummaryCell($rowPt, 0, $rowY, $summaryWidth, $rowHeight, $borderColor, $borderWidth, $textColor, $mutedColor);

            for ($j = 0; $j < $colCount; $j++) {
                $colPt = $outerPoints[$j];
                $cellX = $summaryWidth + ($j * $computedCellSize);
                $cellMidX = $cellX + ($computedCellSize / 2.0);

                $svg .= "    <rect x=\"{$cellX}\" y=\"{$rowY}\" width=\"{$computedCellSize}\" height=\"{$rowHeight}\" fill=\"none\" stroke=\"{$borderColor}\" stroke-width=\"{$borderWidth}\" />\n";

                $asp = $aspectMap[$rowPt['key']][$colPt['key']] ?? null;
                if ($asp) {
                    $aspSymbol = htmlspecialchars($asp['symbol'], ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $aspOrb = htmlspecialchars($asp['orb'], ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $aspTitle = htmlspecialchars($asp['title'], ENT_QUOTES | ENT_XML1, 'UTF-8');

                    $svg .= "    <g>\n";
                    $svg .= "      <title>{$aspTitle}</title>\n";
                    $svg .= "      <text x=\"{$cellMidX}\" y=\"" . ($rowY + ($rowHeight * 0.38)) . "\" font-size=\"9.5\" font-weight=\"bold\" font-family=\"sans-serif\" fill=\"{$asp['color']}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$aspSymbol}</text>\n";
                    $svg .= "      <text x=\"{$cellMidX}\" y=\"" . ($rowY + ($rowHeight * 0.76)) . "\" font-size=\"6.2\" font-family=\"monospace\" fill=\"{$mutedColor}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$aspOrb}</text>\n";
                    $svg .= "    </g>\n";
                }
            }
        }

        // Painel lateral direito: posicoes do mapa externo e em qual casa do mapa interno caem
        $matrixTotalWidth = $summaryWidth + ($colCount * $computedCellSize);
        $panelX = $matrixTotalWidth + 24.0;
        $panelWidth = max(180.0, 900.0 - $panelX);

        if ($panelWidth >= 165.0) {
            $svg .= "    <rect x=\"{$panelX}\" y=\"0\" width=\"{$panelWidth}\" height=\"{$headerHeight}\" fill=\"#fdf2f8\" stroke=\"{$borderColor}\" stroke-width=\"{$borderWidth}\" />\n";
            $svg .= "    <text x=\"" . ($panelX + 8) . "\" y=\"15\" font-size=\"9\" font-weight=\"bold\" font-family=\"sans-serif\" fill=\"#db2777\">POSIÇÕES EXTERNAS</text>\n";
            $svg .= "    <text x=\"" . ($panelX + 8) . "\" y=\"29\" font-size=\"7.5\" font-family=\"sans-serif\" fill=\"{$mutedColor}\">Casa = sobreposição no mapa interno</text>\n";

            for ($j = 0; $j < $colCount; $j++) {
                $outerPt = $outerPoints[$j];
                $pRowY = $headerHeight + ($j * $rowHeight);
                if ($pRowY + $rowHeight > 495.0) {
                    break;
                }
                $svg .= $this->renderPointSummaryCell($outerPt, $panelX, $pRowY, $panelWidth, $rowHeight, $borderColor, $borderWidth, $textColor, $mutedColor);
            }
        }

        $svg .= "  </g>\n";

        return $svg;
    }

    protected function renderPointSummaryCell(
        array $pt,
        float $x,
        float $y,
        float $width,
        float $height,
        string $borderColor,
        float $borderWidth,
        string $textColor,
        string $mutedColor
    ): string {
        $midY = $y + ($height / 2.0);
        $iconSize = 13.0;
        $iconY = $midY - ($iconSize / 2.0);

        $svg = "    <rect x=\"{$x}\" y=\"{$y}\" width=\"{$width}\" height=\"{$height}\" fill=\"none\" stroke=\"{$borderColor}\" stroke-width=\"{$borderWidth}\" />\n";

        if (!empty($pt['iconUri'])) {
            $svg .= "    <image href=\"{$pt['iconUri']}\" x=\"" . ($x + 5) . "\" y=\"{$iconY}\" width=\"{$iconSize}\" height=\"{$iconSize}\" />\n";
        } else {
            $glyph = htmlspecialchars($pt['glyph'], ENT_QUOTES | ENT_XML1, 'UTF-8');
            $svg .= "    <text x=\"" . ($x + 11.5) . "\" y=\"{$midY}\" font-size=\"9\" font-weight=\"bold\" font-family=\"sans-serif\" fill=\"{$textColor}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$glyph}</text>\n";
        }

        $label = htmlspecialchars($pt['label'], ENT_QUOTES | ENT_XML1, 'UTF-8');
        $svg .= "    <text x=\"" . ($x + 22) . "\" y=\"{$midY}\" font-size=\"8.8\" font-weight=\"600\" font-family=\"sans-serif\" fill=\"{$textColor}\" text-anchor=\"start\" dominant-baseline=\"central\">{$label}</text>\n";

        $deg = htmlspecialchars($pt['deg'], ENT_QUOTES | ENT_XML1, 'UTF-8');
        $svg .= "    <text x=\"" . ($x + 78) . "\" y=\"{$midY}\" font-size=\"8\" font-family=\"monospace\" fill=\"{$mutedColor}\" text-anchor=\"start\" dominant-baseline=\"central\">{$deg}</text>\n";

        $signSize = 12.0;
        $signY = $midY - ($signSize / 2.0);
        $signTitle = htmlspecialchars(AstrologyTranslator::translate($pt['signName']), ENT_QUOTES | ENT_XML1, 'UTF-8');

        if (!empty($pt['signIconUri'])) {
            $svg .= "    <g><title>{$signTitle}</title><image href=\"{$pt['signIconUri']}\" x=\"" . ($x + 95) . "\" y=\"{$signY}\" width=\"{$signSize}\" height=\"{$signSize}\" /></g>\n";
        } else {
            $signUni = htmlspecialchars($pt['signUnicode'], ENT_QUOTES | ENT_XML1, 'UTF-8');
            $svg .= "    <text x=\"" . ($x + 101) . "\" y=\"{$midY}\" font-size=\"8.5\" font-family=\"sans-serif\" fill=\"{$textColor}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$signUni}</text>\n";
        }

        $min = htmlspecialchars($pt['min'], ENT_QUOTES | ENT_XML1, 'UTF-8');
        $svg .= "    <text x=\"" . ($x + 111) . "\" y=\"{$midY}\" font-size=\"8\" font-family=\"monospace\" fill=\"{$mutedColor}\" text-anchor=\"start\" dominant-baseline=\"central\">{$min}</text>\n";

        if (!empty($pt['retrograde'])) {
            $svg .= "    <text x=\"" . ($x + $width - 27) . "\" y=\"{$midY}\" font-size=\"7.5\" font-weight=\"bold\" font-family=\"sans-serif\" fill=\"#ef4444\" text-anchor=\"middle\" dominant-baseline=\"central\">R</text>\n";
        }

        if (!empty($pt['house'])) {
            $house = htmlspecialchars("C{$pt['house']}", ENT_QUOTES | ENT_XML1, 'UTF-8');
            $svg .= "    <text x=\"" . ($x + $width - 12) . "\" y=\"{$midY}\" font-size=\"7.8\" font-family=\"sans-serif\" fill=\"{$mutedColor}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$house}</text>\n";
        }

        return $svg;
    }

    protected function extractPoints(
        ChartLayerData $layer,
        array $theme,
        string $themeDir,
        string $ext,
        array &$signIconUris,
        array $houseOverrides = []
    ): array {
        $points = [];

        foreach ($this->participatingPoints as $key => $meta) {
            $data = $layer->planets[$key] ?? $layer->angles[$key] ?? null;
            if (!$data || !isset($data['longitude'])) {
                continue;
            }

            $lon = fmod(fmod((float) $data['longitude'], 360.0) + 360.0, 360.0);
            $signIdx = (int) floor($lon / 30.0);
            $degInSign = fmod($lon, 30.0);
            $d = (int) floor($degInSign);
            $m = (int) round(($degInSign - $d) * 60);
            if ($m >= 60) {
                $d++;
                $m = 0;
            }

            $iconUri = null;
            if (!empty($meta['file'])) {
                $iconPath = $themeDir . '/' . ($theme['assets']['planetsDir'] ?? 'planets/') . $meta['file'];
                if (file_exists($iconPath)) {
                    $iconUri = ChartSvgBuilder::fileToDataUri($iconPath);
                }
            }

            if (!isset($signIconUris[$signIdx])) {
                $signClean = strtolower($this->signNames[$signIdx] ?? '');
                $signPath = $themeDir . '/' . ($theme['assets']['signsDir'] ?? 'signs/') . $signClean . '.' . $ext;
                $signIconUris[$signIdx] = file_exists($signPath) ? ChartSvgBuilder::fileToDataUri($signPath) : null;
            }

            $points[] = [
                'key' => $key,
                'label' => $meta['label'],
                'glyph' => $meta['glyph'],
                'iconUri' => $iconUri,
                'deg' => sprintf('%02d°', $d),
                'min' => sprintf("%02d'", $m),
                'signName' => $this->signNames[$signIdx] ?? '',
                'signIconUri' => $signIconUris[$signIdx],
                'signUnicode' => $this->signUnicodes[$signIdx] ?? '?',
                'house' => $houseOverrides[$key] ?? ($data['house'] ?? null),
                'retrograde' => !empty($data['is_retrograde']),
            ];
        }

        return $points;
    }

    protected function indexAspects(array $aspects, array $theme): array
    {
        $map = [];

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

            $map[$p1][$p2] = [
                'type' => $asp['aspect_type'] ?? '',
                'symbol' => $asp['symbol'] ?? '',
                'orb' => ($asp['orb_formatted'] ?? '') . $stateIndicator,
                'color' => $theme['aspects']['colors'][$asp['aspect_type'] ?? ''] ?? '#94a3b8',
                'title' => "Interno {$p1Pt} {$aspPt} Externo {$p2Pt} ({$asp['orb_formatted']})",
            ];
        }

        return $map;
    }
}
