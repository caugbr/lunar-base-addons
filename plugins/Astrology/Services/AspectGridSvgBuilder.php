<?php

namespace Plugins\Astrology\Services;

use Plugins\Astrology\Models\NatalChart;

class AspectGridSvgBuilder
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

    public function build(NatalChart $chart, array $theme, float $originX, float $originY, float $cellSize = 24.0): string
    {
        $themeSlug = $theme['slug'] ?? 'classic';
        $themeDir = ThemeManager::getThemeDirectory($themeSlug);
        $ext = $theme['assets']['extension'] ?? 'svg';

        $planets = $chart->planets;
        $angles = $chart->angles;
        $aspects = $chart->aspects;
        $fonts = ChartSvgBuilder::resolveFonts($theme);

        // Cache em memoria de icones de signos para evitar leituras repetidas
        $signIconUris = [];

        $points = [];
        foreach ($this->participatingPoints as $key => $meta) {
            $data = $planets[$key] ?? $angles[$key] ?? null;
            if (!$data || !isset($data['longitude'])) {
                continue;
            }

            $lon = (float) $data['longitude'];
            $signIdx = (int) floor(fmod($lon, 360.0) / 30.0);
            $degInSign = fmod($lon, 30.0);
            $d = (int) floor($degInSign);
            $m = (int) round(($degInSign - $d) * 60);

            $iconUri = null;
            if (!empty($meta['file'])) {
                $iconPath = $themeDir . '/' . ($theme['assets']['planetsDir'] ?? 'planets/') . $meta['file'];
                if (file_exists($iconPath)) {
                    $iconUri = ChartSvgBuilder::fileToDataUri($iconPath);
                }
            }

            // Resolucao do icone vetorial do signo
            if (!isset($signIconUris[$signIdx])) {
                $signClean = strtolower($this->signNames[$signIdx] ?? '');
                $signPath = $themeDir . '/' . ($theme['assets']['signsDir'] ?? 'signs/') . $signClean . '.' . $ext;
                $signIconUris[$signIdx] = file_exists($signPath) ? ChartSvgBuilder::fileToDataUri($signPath) : null;
            }

            $points[] = [
                'key'         => $key,
                'label'       => $meta['label'],
                'glyph'       => $meta['glyph'],
                'iconUri'     => $iconUri,
                'deg'         => sprintf("%02d°", $d),
                'min'         => sprintf("%02d'", $m),
                'signIdx'     => $signIdx,
                'signName'    => $this->signNames[$signIdx] ?? '',
                'signIconUri' => $signIconUris[$signIdx],
                'signUnicode' => $this->signUnicodes[$signIdx] ?? '?',
                'house'       => $data['house'] ?? null,
                'retrograde'  => !empty($data['is_retrograde']),
            ];
        }

        $totalPoints = count($points);
        if ($totalPoints === 0) {
            return '';
        }

        // Tabela de dispersao dos aspectos para busca O(1)
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

            $orbStr = '';
            if (!empty($asp['orb_formatted'])) {
                $orbParts = explode('°', $asp['orb_formatted']);
                $dOrb = (int) ($orbParts[0] ?? 0);
                $mOrb = (int) str_replace("'", "", $orbParts[1] ?? '0');
                $orbStr = sprintf("%d°%02d'", $dOrb, $mOrb);
            }

            $p1Pt = AstrologyTranslator::translate($p1);
            $aspPt = AstrologyTranslator::translate($asp['aspect'] ?? '');
            $p2Pt = AstrologyTranslator::translate($p2);

            $aspectData = [
                'type'    => $asp['aspect_type'] ?? '',
                'symbol'  => $asp['symbol'] ?? '',
                'orb'     => $orbStr . $stateIndicator,
                'color'   => $theme['aspects']['colors'][$asp['aspect_type'] ?? ''] ?? '#94a3b8',
                'title'   => "{$p1Pt} {$aspPt} {$p2Pt} ({$asp['orb_formatted']})",
            ];

            $aspectMap[$p1][$p2] = $aspectData;
            $aspectMap[$p2][$p1] = $aspectData;
        }

        $summaryWidth = 170.0;
        $borderColor = $theme['houses']['color'] ?? '#94a3b8';
        $borderWidth = 0.75;
        $textColor = '#334155';
        $mutedTextColor = '#64748b';

        $svg = "  <g class=\"chart-aspect-grid\" transform=\"translate({$originX}, {$originY})\">\n";

        for ($i = 0; $i < $totalPoints; $i++) {
            $rowPt = $points[$i];
            $rowY = $i * $cellSize;
            $cellMidY = $rowY + ($cellSize / 2.0);

            // 1. Coluna de Resumo
            $svg .= "    <rect x=\"0\" y=\"{$rowY}\" width=\"{$summaryWidth}\" height=\"{$cellSize}\" "
                 . "fill=\"none\" stroke=\"{$borderColor}\" stroke-width=\"{$borderWidth}\" />\n";

            // Icone do Planeta
            $planetIconSize = 14.0;
            $planetIconY = $cellMidY - ($planetIconSize / 2.0);

            if (!empty($rowPt['iconUri'])) {
                $svg .= "    <image href=\"{$rowPt['iconUri']}\" x=\"6\" y=\"{$planetIconY}\" width=\"{$planetIconSize}\" height=\"{$planetIconSize}\" />\n";
            } else {
                $escapedGlyph = htmlspecialchars($rowPt['glyph'], ENT_QUOTES | ENT_XML1, 'UTF-8');
                $svg .= "    <text x=\"13\" y=\"{$cellMidY}\" font-size=\"10\" font-weight=\"bold\" "
                     . "font-family=\"{$fonts['body']}\" fill=\"{$textColor}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$escapedGlyph}</text>\n";
            }

            // Nome do Planeta
            $labelEscaped = htmlspecialchars($rowPt['label'], ENT_QUOTES | ENT_XML1, 'UTF-8');
            $svg .= "    <text x=\"24\" y=\"{$cellMidY}\" font-size=\"9.5\" font-weight=\"600\" "
                 . "font-family=\"{$fonts['body']}\" fill=\"{$textColor}\" text-anchor=\"start\" dominant-baseline=\"central\">{$labelEscaped}</text>\n";

            // Grau do Signo
            $degEscaped = htmlspecialchars($rowPt['deg'], ENT_QUOTES | ENT_XML1, 'UTF-8');
            $svg .= "    <text x=\"82\" y=\"{$cellMidY}\" font-size=\"8.5\" font-family=\"{$fonts['mono']}\" "
                 . "fill=\"{$mutedTextColor}\" text-anchor=\"start\" dominant-baseline=\"central\">{$degEscaped}</text>\n";

            // Imagem do Signo (ou unicode fallback)
            $signIconSize = 13.0;
            $signIconY = $cellMidY - ($signIconSize / 2.0);
            $signTitle = htmlspecialchars(AstrologyTranslator::translate($rowPt['signName']), ENT_QUOTES | ENT_XML1, 'UTF-8');

            if (!empty($rowPt['signIconUri'])) {
                $svg .= "    <g>\n";
                $svg .= "      <title>{$signTitle}</title>\n";
                $svg .= "      <image href=\"{$rowPt['signIconUri']}\" x=\"99\" y=\"{$signIconY}\" width=\"{$signIconSize}\" height=\"{$signIconSize}\" />\n";
                $svg .= "    </g>\n";
            } else {
                $escapedSignUnicode = htmlspecialchars($rowPt['signUnicode'], ENT_QUOTES | ENT_XML1, 'UTF-8');
                $svg .= "    <text x=\"105\" y=\"{$cellMidY}\" font-size=\"9\" font-family=\"{$fonts['body']}\" "
                     . "fill=\"{$textColor}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$escapedSignUnicode}</text>\n";
            }

            // Minutos
            $minEscaped = htmlspecialchars($rowPt['min'], ENT_QUOTES | ENT_XML1, 'UTF-8');
            $svg .= "    <text x=\"115\" y=\"{$cellMidY}\" font-size=\"8.5\" font-family=\"{$fonts['mono']}\" "
                 . "fill=\"{$mutedTextColor}\" text-anchor=\"start\" dominant-baseline=\"central\">{$minEscaped}</text>\n";

            // Indicador de Retrogradacao
            if ($rowPt['retrograde']) {
                $svg .= "    <text x=\"142\" y=\"{$cellMidY}\" font-size=\"7.5\" font-weight=\"bold\" "
                     . "font-family=\"{$fonts['body']}\" fill=\"#ef4444\" text-anchor=\"middle\" dominant-baseline=\"central\">R</text>\n";
            }

            // Casa Astrologica
            if ($rowPt['house']) {
                $houseText = htmlspecialchars("C{$rowPt['house']}", ENT_QUOTES | ENT_XML1, 'UTF-8');
                $svg .= "    <text x=\"158\" y=\"{$cellMidY}\" font-size=\"8\" font-family=\"{$fonts['body']}\" "
                     . "fill=\"{$mutedTextColor}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$houseText}</text>\n";
            }

            // 2. Celulas da Escadinha Triangular
            for ($j = 0; $j <= $i; $j++) {
                $colPt = $points[$j];
                $cellX = $summaryWidth + ($j * $cellSize);
                $cellMidX = $cellX + ($cellSize / 2.0);

                $svg .= "    <rect x=\"{$cellX}\" y=\"{$rowY}\" width=\"{$cellSize}\" height=\"{$cellSize}\" "
                     . "fill=\"none\" stroke=\"{$borderColor}\" stroke-width=\"{$borderWidth}\" />\n";

                // Diagonal principal: glifo do planeta
                if ($i === $j) {
                    $svg .= "    <g transform=\"translate(" . ($cellMidX - ($planetIconSize / 2.0)) . ", {$planetIconY})\">\n";
                    $svg .= "      <title>{$rowPt['label']}</title>\n";
                    if (!empty($rowPt['iconUri'])) {
                        $svg .= "      <image href=\"{$rowPt['iconUri']}\" x=\"0\" y=\"0\" width=\"{$planetIconSize}\" height=\"{$planetIconSize}\" />\n";
                    } else {
                        $escapedGlyph = htmlspecialchars($rowPt['glyph'], ENT_QUOTES | ENT_XML1, 'UTF-8');
                        $svg .= "      <text x=\"" . ($planetIconSize / 2.0) . "\" y=\"" . ($planetIconSize / 2.0) . "\" font-size=\"10\" font-weight=\"bold\" "
                             . "font-family=\"{$fonts['body']}\" fill=\"{$textColor}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$escapedGlyph}</text>\n";
                    }
                    $svg .= "    </g>\n";
                    continue;
                }

                // Celula de aspecto entre P1 e P2
                $asp = $aspectMap[$rowPt['key']][$colPt['key']] ?? null;
                if ($asp) {
                    $aspSymbol = htmlspecialchars($asp['symbol'], ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $aspOrb = htmlspecialchars($asp['orb'], ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $aspTitle = htmlspecialchars($asp['title'], ENT_QUOTES | ENT_XML1, 'UTF-8');

                    $svg .= "    <g>\n";
                    $svg .= "      <title>{$aspTitle}</title>\n";
                    $svg .= "      <text x=\"{$cellMidX}\" y=\"" . ($rowY + 9.5) . "\" font-size=\"10\" font-weight=\"bold\" "
                         . "font-family=\"{$fonts['body']}\" fill=\"{$asp['color']}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$aspSymbol}</text>\n";
                    $svg .= "      <text x=\"{$cellMidX}\" y=\"" . ($rowY + 18.5) . "\" font-size=\"6.5\" font-family=\"{$fonts['mono']}\" "
                         . "fill=\"{$mutedTextColor}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$aspOrb}</text>\n";
                    $svg .= "    </g>\n";
                }
            }
        }

        $svg .= "  </g>\n";

        return $svg;
    }
}
