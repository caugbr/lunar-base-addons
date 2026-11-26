<?php

namespace Plugins\Astrology\Services\Concerns;

use InvalidArgumentException;
use Plugins\Astrology\Services\ChartSvgBuilder;
use Plugins\Astrology\Services\AstrologyTranslator;

trait RendersMandalaPrimitives
{
    protected function toDisplayAngle(float $eclipticLongitude, float $ascendantLongitude): float
    {
        $ascSignIndex = (int) floor($ascendantLongitude / 30.0);
        $ascSignCenter = ($ascSignIndex * 30.0) + 15.0;

        $diff = fmod(fmod($eclipticLongitude - $ascSignCenter, 360.0) + 360.0, 360.0);

        return fmod(180.0 + $diff, 360.0);
    }

    protected function polarToCartesian(float $angleDeg, float $radius, float $cx, float $cy): array
    {
        $rad = deg2rad($angleDeg);

        return [
            'x' => round($cx + ($radius * cos($rad)), 2),
            'y' => round($cy - ($radius * sin($rad)), 2),
        ];
    }

    protected function resolveAngularCollisions(array $items, float $minSeparation, int $iterations = 40): array
    {
        usort($items, fn (array $a, array $b) => $a['exactAngle'] <=> $b['exactAngle']);

        $count = count($items);
        if ($count < 2) {
            return $items;
        }

        // Evita travamento caso haja muitos astros e separação grande demais
        $effectiveMinSep = min($minSeparation, (360.0 / $count) - 0.5);

        for ($iter = 0; $iter < $iterations; $iter++) {
            $moved = false;

            for ($i = 0; $i < $count; $i++) {
                $nextIdx = ($i === $count - 1) ? 0 : ($i + 1);

                // Diferença angular com sinal no intervalo [-180, +180)
                $diff = fmod($items[$nextIdx]['renderAngle'] - $items[$i]['renderAngle'] + 540.0, 360.0) - 180.0;

                if ($diff < $effectiveMinSep) {
                    $overlap = ($effectiveMinSep - $diff) / 2.0;
                    $items[$i]['renderAngle'] = fmod($items[$i]['renderAngle'] - $overlap + 360.0, 360.0);
                    $items[$nextIdx]['renderAngle'] = fmod($items[$nextIdx]['renderAngle'] + $overlap + 360.0, 360.0);
                    $moved = true;
                }
            }

            if (!$moved) {
                break;
            }
        }

        return $items;
    }

    protected function renderBands(array $bands, float $ascLon, float $cx, float $cy): string
    {
        $allowedCounts = [1, 2, 3, 4, 6, 12, 36];
        $output = '';

        foreach ($bands as $band) {
            $name = $band['name'] ?? 'unnamed_band';
            $colors = $band['colors'] ?? [];

            if (!is_array($colors) || empty($colors)) {
                continue;
            }

            $count = count($colors);
            if (!in_array($count, $allowedCounts, true)) {
                throw new InvalidArgumentException(
                    "A faixa '{$name}' possui {$count} cores configuradas. "
                    . 'Quantidades permitidas: ' . implode(', ', $allowedCounts) . '.'
                );
            }

            $innerR = (float) ($band['innerRadius'] ?? 0);
            $outerR = (float) ($band['outerRadius'] ?? 0);

            if ($innerR >= $outerR || $innerR <= 0) {
                continue;
            }

            $opacity = isset($band['opacity']) ? max(0.0, min(1.0, (float) $band['opacity'])) : 1.0;
            $eclipticOffset = (float) ($band['eclipticOffset'] ?? 0.0);

            if ($count === 1) {
                $color = htmlspecialchars($colors[0], ENT_QUOTES | ENT_XML1, 'UTF-8');
                $midR = ($innerR + $outerR) / 2.0;
                $strokeW = $outerR - $innerR;
                $output .= "    <circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$midR}\" fill=\"none\" "
                        . "stroke=\"{$color}\" stroke-width=\"{$strokeW}\" opacity=\"{$opacity}\" />\n";
                continue;
            }

            $step = 360.0 / $count;

            for ($i = 0; $i < $count; $i++) {
                $color = htmlspecialchars($colors[$i], ENT_QUOTES | ENT_XML1, 'UTF-8');
                $startLon = $eclipticOffset + ($i * $step);
                $endLon = $startLon + $step;

                $startAngle = $this->toDisplayAngle($startLon, $ascLon);
                $endAngle = $this->toDisplayAngle($endLon, $ascLon);

                $overlap = 0.04;
                $p1 = $this->polarToCartesian($startAngle, $outerR, $cx, $cy);
                $p2 = $this->polarToCartesian($endAngle + $overlap, $outerR, $cx, $cy);
                $p3 = $this->polarToCartesian($endAngle + $overlap, $innerR, $cx, $cy);
                $p4 = $this->polarToCartesian($startAngle, $innerR, $cx, $cy);

                $largeArc = ($step > 180.0) ? 1 : 0;

                $pathD = "M {$p1['x']} {$p1['y']} "
                       . "A {$outerR} {$outerR} 0 {$largeArc} 0 {$p2['x']} {$p2['y']} "
                       . "L {$p3['x']} {$p3['y']} "
                       . "A {$innerR} {$innerR} 0 {$largeArc} 1 {$p4['x']} {$p4['y']} Z";

                $output .= "    <path d=\"{$pathD}\" fill=\"{$color}\" opacity=\"{$opacity}\" stroke=\"none\" />\n";
            }
        }

        return $output;
    }

    protected function renderSigns(float $ascLon, array $theme, string $themeDir, float $cx, float $cy): string
    {
        $fonts = ChartSvgBuilder::resolveFonts($theme);
        $radius = (float) ($theme['radii']['signsDistance'] ?? 390);
        $sWidth = (float) ($theme['dimensions']['signWidth'] ?? 48);
        $sHeight = (float) ($theme['dimensions']['signHeight'] ?? 48);
        $ext = $theme['assets']['extension'] ?? 'svg';
        $alignToCenter = !empty($theme['signs']['alignToCenter']);

        $signs = [
            'Aries', 'Taurus', 'Gemini', 'Cancer',
            'Leo', 'Virgo', 'Libra', 'Scorpio',
            'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces',
        ];
        $unicodes = ['♈', '♉', '♊', '♋', '♌', '♍', '♎', '♏', '♐', '♑', '♒', '♓'];
        $output = '';

        foreach ($signs as $index => $signName) {
            $signCenterLon = ($index * 30.0) + 15.0;
            $displayAngle = $this->toDisplayAngle($signCenterLon, $ascLon);
            $pos = $this->polarToCartesian($displayAngle, $radius, $cx, $cy);

            $transform = "translate({$pos['x']}, {$pos['y']})";
            if ($alignToCenter) {
                $rotation = round(90.0 - $displayAngle, 2);
                $transform .= " rotate({$rotation})";
            }

            $cleanName = strtolower($signName);
            $iconFile = $themeDir . '/' . ($theme['assets']['signsDir'] ?? 'signs/') . $cleanName . '.' . $ext;
            $iconDataUri = ChartSvgBuilder::fileToDataUri($iconFile);
            $translatedSign = AstrologyTranslator::translate($signName);
            $escapedSignName = htmlspecialchars($translatedSign, ENT_QUOTES | ENT_XML1, 'UTF-8');

            $output .= "    <g class=\"chart-sign-glyph\" transform=\"{$transform}\">\n";
            $output .= "      <title>{$escapedSignName}</title>\n";
            if ($iconDataUri) {
                $output .= "      <image href=\"{$iconDataUri}\" x=\"-" . ($sWidth / 2) . "\" y=\"-" . ($sHeight / 2) . "\" width=\"{$sWidth}\" height=\"{$sHeight}\" />\n";
            } else {
                $symbol = $unicodes[$index] ?? '';
                $output .= "      <text x=\"0\" y=\"0\" font-size=\"20\" fill=\"#475569\" font-family=\"{$fonts['body']}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$symbol}</text>\n";
            }
            $output .= "    </g>\n";
        }

        return $output;
    }

    protected function renderHouseLines(array $houses, float $ascLon, array $theme, float $cx, float $cy): string
    {
        if (empty($houses) || empty($theme['houses']['showLines'])) {
            return '';
        }

        $innerR = (float) ($theme['radii']['housesInnerRadius'] ?? 145);
        $outerR = (float) ($theme['radii']['housesOuterRadius'] ?? 340);
        $numberR = $innerR + 18;
        $degreeR = $outerR - 12;

        $axisColor = $theme['houses']['axisColor'] ?? '#38bdf8';
        $cuspColor = $theme['houses']['color'] ?? '#475569';
        $axisWidth = $theme['houses']['axisStrokeWidth'] ?? 2.0;
        $cuspWidth = $theme['houses']['strokeWidth'] ?? 1.0;
        $showNumbers = !empty($theme['houses']['showNumbers']);
        $numberColor = $theme['houses']['numberColor'] ?? '#64748b';
        $numberSize = (float) ($theme['houses']['numberSize'] ?? 10.5);
        $degSize = (float) ($theme['houses']['degSize'] ?? 8.5);
        $fonts = ChartSvgBuilder::resolveFonts($theme);

        $output = '';

        for ($i = 1; $i <= 12; $i++) {
            if (!isset($houses[$i])) {
                continue;
            }

            $cuspLon = (float) $houses[$i]['longitude'];
            $displayAngle = $this->toDisplayAngle($cuspLon, $ascLon);

            $p1 = $this->polarToCartesian($displayAngle, $innerR, $cx, $cy);
            $p2 = $this->polarToCartesian($displayAngle, $outerR, $cx, $cy);

            $isAxis = in_array($i, [1, 4, 7, 10], true);
            $stroke = $isAxis ? $axisColor : $cuspColor;
            $width = $isAxis ? $axisWidth : $cuspWidth;
            $opacity = $isAxis ? 0.95 : 0.6;

            $formattedPos = AstrologyTranslator::translateFormatted($houses[$i]['formatted']);
            $escapedTitle = htmlspecialchars("Cúspide Casa {$i}: {$formattedPos}", ENT_QUOTES | ENT_XML1, 'UTF-8');

            $output .= "    <line x1=\"{$p1['x']}\" y1=\"{$p1['y']}\" x2=\"{$p2['x']}\" y2=\"{$p2['y']}\" "
                    . "stroke=\"{$stroke}\" stroke-width=\"{$width}\" stroke-opacity=\"{$opacity}\">\n";
            $output .= "      <title>{$escapedTitle}</title>\n";
            $output .= "    </line>\n";

            $cuspDeg = (int) floor(fmod($cuspLon, 30.0));
            $degPos = $this->polarToCartesian($displayAngle + 2.0, $degreeR, $cx, $cy);
            $output .= "    <text x=\"{$degPos['x']}\" y=\"{$degPos['y']}\" "
                    . "font-size=\"{$degSize}\" font-weight=\"600\" fill=\"#64748b\" font-family=\"{$fonts['body']}\" text-anchor=\"middle\" dominant-baseline=\"central\">\n";
            $output .= "      {$cuspDeg}°\n";
            $output .= "    </text>\n";

            if ($showNumbers) {
                $next = ($i === 12) ? 1 : ($i + 1);
                if (isset($houses[$next])) {
                    $nextLon = (float) $houses[$next]['longitude'];
                    $nextAngle = $this->toDisplayAngle($nextLon, $ascLon);

                    $fonts = ChartSvgBuilder::resolveFonts($theme);
                    $diff = fmod($nextAngle - $displayAngle + 360.0, 360.0);
                    $midAngle = fmod($displayAngle + ($diff / 2.0), 360.0);
                    $numPos = $this->polarToCartesian($midAngle, $numberR, $cx, $cy);

                    $output .= "    <text x=\"{$numPos['x']}\" y=\"{$numPos['y']}\" "
                            . "font-size=\"{$numberSize}\" font-weight=\"600\" fill=\"{$numberColor}\" font-family=\"{$fonts['body']}\" text-anchor=\"middle\" dominant-baseline=\"central\">\n";
                    $output .= "      {$i}\n";
                    $output .= "    </text>\n";
                }
            }
        }

        return $output;
    }

    protected function renderFooter(array $theme, int $vbWidth, int $vbHeight): string
    {
        if (empty($theme['modules']['footerText'])) {
            return '';
        }

        $footerText = $theme['layout']['footer']['text'] ?? 'Lunar Base Astrology';
        $escapedText = htmlspecialchars($footerText, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $fontSize = htmlspecialchars($theme['layout']['footer']['fontSize'] ?? 12, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $color = htmlspecialchars($theme['layout']['footer']['color'] ?? '#94a3b8', ENT_QUOTES | ENT_XML1, 'UTF-8');
        $bottomDistance = htmlspecialchars($theme['layout']['footer']['bottomDistance'] ?? 52, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $textY = $vbHeight - $bottomDistance;
        $centerX = $vbWidth / 2.0;
        $fonts = ChartSvgBuilder::resolveFonts($theme);

        return "  <!-- Rodape -->\n"
             . "  <text x=\"{$centerX}\" y=\"{$textY}\" font-size=\"{$fontSize}\" font-family=\"{$fonts['body']}\" "
             . "fill=\"{$color}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$escapedText}</text>\n";
    }

    protected function getDefaultPlanetGlyphs(): array
    {
        return [
            'Sun'        => ['symbol' => '☉', 'color' => '#f59e0b'],
            'Moon'       => ['symbol' => '☽', 'color' => '#0284c7'],
            'Mercury'    => ['symbol' => '☿', 'color' => '#8b5cf6'],
            'Venus'      => ['symbol' => '♀', 'color' => '#10b981'],
            'Mars'       => ['symbol' => '♂', 'color' => '#ef4444'],
            'Jupiter'    => ['symbol' => '♃', 'color' => '#f97316'],
            'Saturn'     => ['symbol' => '♄', 'color' => '#64748b'],
            'Uranus'     => ['symbol' => '♅', 'color' => '#06b6d4'],
            'Neptune'    => ['symbol' => '♆', 'color' => '#3b82f6'],
            'Pluto'      => ['symbol' => '♇', 'color' => '#475569'],
            'North Node' => ['symbol' => '☊', 'color' => '#6366f1'],
            'South Node' => ['symbol' => '☋', 'color' => '#6366f1'],
            'Chiron'     => ['symbol' => '⚷', 'color' => '#a855f7'],
            'Lilith'     => ['symbol' => '⚸', 'color' => '#1e293b'],
            'Ascendant'  => ['symbol' => 'AC', 'color' => '#0284c7'],
            'MC'         => ['symbol' => 'MC', 'color' => '#0284c7'],
        ];
    }

    protected function resolveAxesPoints(array $angles, array $houses): array
    {
        $ascLon = $angles['Ascendant']['longitude'] ?? $houses[1]['longitude'] ?? null;
        $mcLon = $angles['MC']['longitude'] ?? $houses[10]['longitude'] ?? null;
        $dscLon = $angles['Descendant']['longitude']
            ?? ($ascLon !== null ? fmod((float) $ascLon + 180.0, 360.0) : null);
        $icLon = $angles['Imum Coeli']['longitude']
            ?? ($mcLon !== null ? fmod((float) $mcLon + 180.0, 360.0) : null);

        $points = [];

        if ($ascLon !== null) {
            $points['asc'] = [
                'name'      => 'Ascendente',
                'longitude' => (float) $ascLon,
                'formatted' => $angles['Ascendant']['formatted'] ?? $houses[1]['formatted'] ?? '',
            ];
        }

        if ($dscLon !== null) {
            $points['dsc'] = [
                'name'      => 'Descendente',
                'longitude' => (float) $dscLon,
                'formatted' => $angles['Descendant']['formatted'] ?? $houses[7]['formatted'] ?? '',
            ];
        }

        if ($mcLon !== null) {
            $points['mc'] = [
                'name'      => 'Meio do Céu',
                'longitude' => (float) $mcLon,
                'formatted' => $angles['MC']['formatted'] ?? $houses[10]['formatted'] ?? '',
            ];
        }

        if ($icLon !== null) {
            $points['ic'] = [
                'name'      => 'Fundo do Céu',
                'longitude' => (float) $icLon,
                'formatted' => $angles['Imum Coeli']['formatted'] ?? $houses[4]['formatted'] ?? '',
            ];
        }

        return $points;
    }

    protected function renderAxisMarkers(
        array $angles,
        array $houses,
        float $chartAscLon,
        array $theme,
        string $themeDir,
        float $cx,
        float $cy,
        ?array $axesOverride = null,
        string $titlePrefix = ''
    ): string {
        $cfg = $axesOverride ?? ($theme['axes'] ?? []);
        if (empty($cfg['showMarkers'])) {
            return '';
        }

        $axes = $this->resolveAxesPoints($angles, $houses);
        if (empty($axes)) {
            return '';
        }

        $fonts = ChartSvgBuilder::resolveFonts($theme);
        $mode = $cfg['mode'] ?? 'auto'; // 'auto' | 'image' | 'text'
        $distance = (float) ($cfg['distance'] ?? 468);
        $imgW = (float) ($cfg['imageWidth'] ?? 28);
        $imgH = (float) ($cfg['imageHeight'] ?? 28);
        $fontSize = (float) ($cfg['fontSize'] ?? 11);
        $fontWeight = htmlspecialchars((string) ($cfg['fontWeight'] ?? 'bold'), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $color = htmlspecialchars((string) ($cfg['color'] ?? ($theme['houses']['axisColor'] ?? '#0284c7')), ENT_QUOTES | ENT_XML1, 'UTF-8');

        $showTicks = !empty($cfg['showTicks']);
        $tickInnerR = (float) ($cfg['ticksInnerRadius'] ?? ($distance - 20));
        $tickOuterR = (float) ($cfg['ticksOuterRadius'] ?? ($distance - 12));
        $tickColor = htmlspecialchars((string) ($cfg['tickColor'] ?? $color), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $tickWidth = (float) ($cfg['tickStrokeWidth'] ?? 2.0);

        $defaultLabels = [
            'asc' => 'ASC',
            'dsc' => 'DSC',
            'mc'  => 'MC',
            'ic'  => 'FC',
        ];
        $labelsCfg = $cfg['labels'] ?? [];
        $imagesCfg = $cfg['images'] ?? [];

        $output = '';

        foreach ($axes as $axisKey => $axis) {
            $hasCustomLabel = array_key_exists($axisKey, $labelsCfg);
            $labelText = $hasCustomLabel ? (string) ($labelsCfg[$axisKey] ?? '') : $defaultLabels[$axisKey];
            $imageRel = $imagesCfg[$axisKey] ?? null;

            $imageUri = '';
            if (!empty($imageRel) && $mode !== 'text') {
                $imageUri = ChartSvgBuilder::fileToDataUri($themeDir . '/' . ltrim($imageRel, '/'));
            }

            // Se a imagem foi desabilitada/não encontrada e o label foi explicitamente zerado (null/''), pula este eixo
            if ($imageUri === '' && ($mode === 'image' || $labelText === '')) {
                continue;
            }

            $ang = $this->toDisplayAngle($axis['longitude'], $chartAscLon);
            $pos = $this->polarToCartesian($ang, $distance, $cx, $cy);

            $formatted = $axis['formatted'] !== ''
                ? ': ' . AstrologyTranslator::translateFormatted($axis['formatted'])
                : '';
            $prefix = $titlePrefix !== '' ? "{$titlePrefix} " : '';
            $escapedTitle = htmlspecialchars("{$prefix}{$axis['name']}{$formatted}", ENT_QUOTES | ENT_XML1, 'UTF-8');

            $output .= "    <g class=\"chart-axis-marker chart-axis-{$axisKey}\">\n";
            $output .= "      <title>{$escapedTitle}</title>\n";

            if ($showTicks) {
                $p1 = $this->polarToCartesian($ang, $tickInnerR, $cx, $cy);
                $p2 = $this->polarToCartesian($ang, $tickOuterR, $cx, $cy);
                $output .= "      <line x1=\"{$p1['x']}\" y1=\"{$p1['y']}\" x2=\"{$p2['x']}\" y2=\"{$p2['y']}\" stroke=\"{$tickColor}\" stroke-width=\"{$tickWidth}\" />\n";
            }

            if ($imageUri !== '') {
                $x = round($pos['x'] - ($imgW / 2.0), 2);
                $y = round($pos['y'] - ($imgH / 2.0), 2);
                $output .= "      <image href=\"{$imageUri}\" x=\"{$x}\" y=\"{$y}\" width=\"{$imgW}\" height=\"{$imgH}\" />\n";
            } else {
                $escapedLabel = htmlspecialchars($labelText, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $output .= "      <text x=\"{$pos['x']}\" y=\"{$pos['y']}\" font-size=\"{$fontSize}\" font-weight=\"{$fontWeight}\" "
                        . "font-family=\"{$fonts['heading']}\" fill=\"{$color}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$escapedLabel}</text>\n";
            }

            $output .= "    </g>\n";
        }

        return $output;
    }
}
