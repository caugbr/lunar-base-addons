<?php

namespace Plugins\Astrology\Services;

use Illuminate\Support\Str;
use Plugins\Astrology\DTOs\BiwheelChartData;
use Plugins\Astrology\Services\Concerns\RendersMandalaPrimitives;

class BiwheelChartSvgBuilder
{
    use RendersMandalaPrimitives;

    public function build(BiwheelChartData $data, ?string $themeSlug = 'classic'): string
    {
        if (!$data->isReady()) {
            return $this->renderEmptyState();
        }

        $theme = ThemeManager::getBiwheelTheme($themeSlug ?? 'classic');
        $themeDir = $theme['path'] ?? ThemeManager::getThemeDirectory($theme['slug'] ?? 'classic') ?? '';

        $ascendantLon = $data->inner->getAscendantLongitude();

        $vbWidth = (int) ($theme['canvas']['viewBoxWidth'] ?? 1000);
        $vbHeight = (int) ($theme['canvas']['viewBoxHeight'] ?? 1414);
        $bgColor = $theme['canvas']['backgroundColor'] ?? '#ffffff';

        $bgAsset = $theme['assets']['background'] ?? null;
        $bgDataUri = $bgAsset ? ChartSvgBuilder::fileToDataUri($themeDir . '/' . $bgAsset) : '';

        $baseFile = $themeDir . '/' . ($theme['assets']['base'] ?? 'base-biwheel.svg');
        $baseDataUri = ChartSvgBuilder::fileToDataUri($baseFile);

        $svg = "<svg viewBox=\"0 0 {$vbWidth} {$vbHeight}\" xmlns=\"http://www.w3.org/2000/svg\" class=\"natal-chart-svg natal-chart-biwheel-svg\">\n";

        if ($bgColor && $bgColor !== 'transparent') {
            $escapedBg = htmlspecialchars($bgColor, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $svg .= "  <rect width=\"{$vbWidth}\" height=\"{$vbHeight}\" fill=\"{$escapedBg}\" />\n";
        }

        if ($bgDataUri) {
            $svg .= "  <image href=\"{$bgDataUri}\" x=\"0\" y=\"0\" width=\"{$vbWidth}\" height=\"{$vbHeight}\" />\n";
        }

        $svg .= $this->renderBiwheelHeader($data, $theme, $themeDir);

        $wheelConfig = $theme['layout']['wheel'] ?? [];
        $targetCx = (float) ($wheelConfig['centerX'] ?? 500);
        $targetCy = (float) ($wheelConfig['centerY'] ?? 475);
        $scale = (float) ($wheelConfig['scale'] ?? 0.80);

        $svg .= "  <!-- Mandala Bi-Wheel Escalada -->\n";
        $svg .= "  <g id=\"mandala-wheel\" transform=\"translate({$targetCx}, {$targetCy}) scale({$scale}) translate(-500, -500)\">\n";

        if (!empty($theme['bands']) && is_array($theme['bands'])) {
            $svg .= "    <g class=\"chart-bands\">\n";
            $svg .= $this->renderBands($theme['bands'], $ascendantLon, 500, 500);
            $svg .= "    </g>\n";
        }

        if ($baseDataUri) {
            $svg .= "    <image href=\"{$baseDataUri}\" x=\"0\" y=\"0\" width=\"1000\" height=\"1000\" />\n";
        }

        $svg .= "    <g class=\"chart-signs\">\n";
        $svg .= $this->renderSigns($ascendantLon, $theme, $themeDir, 500, 500);
        $svg .= "    </g>\n";

        $svg .= "    <g class=\"chart-houses\">\n";
        $svg .= $this->renderHouseLines($data->inner->houses, $ascendantLon, $theme, 500, 500);
        $svg .= "    </g>\n";

        $svg .= "    <g class=\"chart-cross-aspects\">\n";
        $svg .= $this->renderCrossAspectLines($data, $ascendantLon, $theme, 500, 500);
        $svg .= "    </g>\n";

        $innerRing = $theme['rings']['inner'] ?? [];
        $svg .= "    <g class=\"chart-planets-inner\">\n";
        $svg .= $this->renderRingPlanets($data->inner->planets, $ascendantLon, $innerRing, $theme, $themeDir, 500, 500, 'inner');
        $svg .= "    </g>\n";

        $outerRing = $theme['rings']['outer'] ?? [];
        $svg .= "    <g class=\"chart-planets-outer\">\n";
        $svg .= $this->renderRingPlanets($data->outer->planets, $ascendantLon, $outerRing, $theme, $themeDir, 500, 500, 'outer');
        $svg .= "    </g>\n";

        if (!empty($outerRing['showAngleMarkers']) && !empty($data->outer->angles)) {
            $svg .= "    <g class=\"chart-outer-angles\">\n";
            $svg .= $this->renderOuterAngleMarkers($data->outer->angles, $ascendantLon, 500, 500);
            $svg .= "    </g>\n";
        }

        if (!empty($outerRing['showHouseTicks']) && !empty($data->outer->houses)) {
            $svg .= "    <g class=\"chart-outer-house-ticks\">\n";
            $svg .= $this->renderOuterHouseTicks($data->outer->houses, $ascendantLon, 500, 500);
            $svg .= "    </g>\n";
        }

        $svg .= "  </g>\n";

        $gridConfig = $theme['layout']['crossAspectGrid'] ?? [];
        $gridX = (float) ($gridConfig['x'] ?? 50);
        $gridY = (float) ($gridConfig['y'] ?? 885);
        $gridCellSize = (float) ($gridConfig['cellSize'] ?? 28);
        $summaryWidth = (float) ($gridConfig['summaryWidth'] ?? 165);

        $crossGridBuilder = app(CrossAspectGridSvgBuilder::class);
        $svg .= $crossGridBuilder->build($data, $theme, $gridX, $gridY, $gridCellSize, $summaryWidth);

        $svg .= $this->renderFooter($theme, $vbWidth, $vbHeight);

        $svg .= "</svg>";

        return $svg;
    }

    protected function renderBiwheelHeader(BiwheelChartData $data, array $theme, string $themeDir): string
    {
        $showInfo = !empty($theme['modules']['chartInfo']);
        $showLogo = !empty($theme['modules']['logo']);

        if (!$showInfo && !$showLogo) {
            return '';
        }

        $headerCfg = $theme['layout']['header'] ?? [];
        $innerX = (float) ($headerCfg['innerX'] ?? 50);
        $outerX = (float) ($headerCfg['outerX'] ?? 430);
        $baseY = (float) ($headerCfg['y'] ?? 40);

        $svg = "  <!-- Cabecalho Duplo -->\n";
        $svg .= "  <g class=\"chart-header\">\n";

        if ($showInfo) {
            $innerName = htmlspecialchars(Str::limit($data->inner->label, 36), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $innerSub = htmlspecialchars(Str::limit($data->inner->subtitle, 56), ENT_QUOTES | ENT_XML1, 'UTF-8');
            // $houseSys = htmlspecialchars(ucfirst($data->inner->houseSystem), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $houseSys = htmlspecialchars(AstrologyTranslator::translate($data->inner->houseSystem), ENT_QUOTES | ENT_XML1, 'UTF-8');

            $outerName = htmlspecialchars(Str::limit($data->outer->label, 34), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $outerSub = htmlspecialchars(Str::limit($data->outer->subtitle, 52), ENT_QUOTES | ENT_XML1, 'UTF-8');

            $svg .= "    <circle cx=\"" . ($innerX + 4) . "\" cy=\"" . ($baseY - 4) . "\" r=\"4\" fill=\"#0284c7\" />\n";
            $svg .= "    <text x=\"" . ($innerX + 13) . "\" y=\"{$baseY}\" font-size=\"13.5\" font-weight=\"bold\" font-family=\"sans-serif\" fill=\"#1e293b\">Interno: {$innerName}</text>\n";
            $svg .= "    <text x=\"{$innerX}\" y=\"" . ($baseY + 17) . "\" font-size=\"9.5\" font-family=\"sans-serif\" fill=\"#64748b\">{$innerSub}</text>\n";
            $svg .= "    <text x=\"{$innerX}\" y=\"" . ($baseY + 32) . "\" font-size=\"9.5\" font-family=\"sans-serif\" fill=\"#64748b\">Casas internas: {$houseSys}</text>\n";

            $svg .= "    <circle cx=\"" . ($outerX + 4) . "\" cy=\"" . ($baseY - 4) . "\" r=\"4\" fill=\"#db2777\" />\n";
            $svg .= "    <text x=\"" . ($outerX + 13) . "\" y=\"{$baseY}\" font-size=\"13.5\" font-weight=\"bold\" font-family=\"sans-serif\" fill=\"#1e293b\">Externo: {$outerName}</text>\n";
            $svg .= "    <text x=\"{$outerX}\" y=\"" . ($baseY + 17) . "\" font-size=\"9.5\" font-family=\"sans-serif\" fill=\"#64748b\">{$outerSub}</text>\n";
            $svg .= "    <text x=\"{$outerX}\" y=\"" . ($baseY + 32) . "\" font-size=\"9.5\" font-family=\"sans-serif\" fill=\"#64748b\">Aspectos cruzados: " . count($data->crossAspects) . "</text>\n";
        }

        if ($showLogo && !empty($theme['assets']['logo'])) {
            $logoPath = $themeDir . '/' . $theme['assets']['logo'];
            $logoUri = ChartSvgBuilder::fileToDataUri($logoPath);
            if ($logoUri) {
                $svg .= "    <image href=\"{$logoUri}\" x=\"770\" y=\"30\" width=\"180\" height=\"75\" preserveAspectRatio=\"xMaxYMin meet\" />\n";
            }
        }

        $svg .= "  </g>\n";

        return $svg;
    }

    protected function renderCrossAspectLines(BiwheelChartData $data, float $ascLon, array $theme, float $cx, float $cy): string
    {
        if (empty($theme['aspects']['showLines']) || empty($data->crossAspects)) {
            return '';
        }

        $radius = (float) ($theme['radii']['aspectsInnerRadius'] ?? 144);
        $innerPoints = array_merge($data->inner->planets, $data->inner->angles);
        $outerPoints = array_merge($data->outer->planets, $data->outer->angles);

        $showAngleLines = (bool) ($theme['aspects']['showAngleLines'] ?? false);
        $showNodeLines = (bool) ($theme['aspects']['showNodeLines'] ?? false);

        $angleKeys = ['Ascendant', 'MC', 'Descendant', 'Imum Coeli'];
        $nodeKeys = ['North Node', 'South Node', 'True Node', 'mean Node'];
        $strokeWidth = (float) ($theme['aspects']['strokeWidth'] ?? 1.4);

        $lines = '';

        foreach ($data->crossAspects as $asp) {
            $p1Name = $asp['point1'] ?? '';
            $p2Name = $asp['point2'] ?? '';

            if (!$showAngleLines && (in_array($p1Name, $angleKeys, true) || in_array($p2Name, $angleKeys, true))) {
                continue;
            }

            if (!$showNodeLines && (in_array($p1Name, $nodeKeys, true) || in_array($p2Name, $nodeKeys, true))) {
                continue;
            }

            $p1 = $innerPoints[$p1Name] ?? null;
            $p2 = $outerPoints[$p2Name] ?? null;

            if (!$p1 || !$p2 || !isset($p1['longitude'], $p2['longitude'])) {
                continue;
            }

            $ang1 = $this->toDisplayAngle((float) $p1['longitude'], $ascLon);
            $ang2 = $this->toDisplayAngle((float) $p2['longitude'], $ascLon);

            $pt1 = $this->polarToCartesian($ang1, $radius, $cx, $cy);
            $pt2 = $this->polarToCartesian($ang2, $radius, $cx, $cy);

            $color = $theme['aspects']['colors'][$asp['aspect_type'] ?? ''] ?? '#94a3b8';
            $isMinor = ($asp['category'] ?? '') === 'minor';
            $dash = $isMinor ? 'stroke-dasharray="3,3"' : '';
            $opacity = $isMinor ? '0.45' : '0.80';

            $p1Pt = AstrologyTranslator::translate($p1Name);
            $aspPt = AstrologyTranslator::translate($asp['aspect']);
            $p2Pt = AstrologyTranslator::translate($p2Name);

            $escapedTitle = htmlspecialchars(
                "Interno {$p1Pt} {$aspPt} Externo {$p2Pt} ({$asp['orb_formatted']})",
                ENT_QUOTES | ENT_XML1,
                'UTF-8'
            );

            $lines .= "    <line x1=\"{$pt1['x']}\" y1=\"{$pt1['y']}\" x2=\"{$pt2['x']}\" y2=\"{$pt2['y']}\" "
                   . "stroke=\"{$color}\" stroke-width=\"{$strokeWidth}\" stroke-opacity=\"{$opacity}\" {$dash}>\n";
            $lines .= "      <title>{$escapedTitle}</title>\n";
            $lines .= "    </line>\n";
        }

        return $lines;
    }

    protected function renderRingPlanets(
        array $planets,
        float $ascLon,
        array $ringConfig,
        array $theme,
        string $themeDir,
        float $cx,
        float $cy,
        string $ringKey
    ): string {
        if (empty($planets)) {
            return '';
        }

        $baseRadius = (float) ($ringConfig['planetsDistance'] ?? ($ringKey === 'outer' ? 447 : 240));
        $pWidth = (float) ($ringConfig['planetWidth'] ?? 30);
        $pHeight = (float) ($ringConfig['planetHeight'] ?? 30);
        $ext = $theme['assets']['extension'] ?? 'svg';

        $ticksInnerR = (float) ($ringConfig['ticksInnerRadius'] ?? ($ringKey === 'outer' ? 376 : 268));
        $ticksOuterR = (float) ($ringConfig['ticksOuterRadius'] ?? ($ringKey === 'outer' ? 384 : 276));

        $defaultTickColor = $ringKey === 'outer' ? '#db2777' : '#0284c7';
        $tickColor = $ringConfig['tickColor'] ?? ($theme['ticks']['color'] ?? $defaultTickColor);
        $tickWidth = (float) ($ringConfig['tickWidth'] ?? ($theme['ticks']['strokeWidth'] ?? 1.5));
        $guideColor = $theme['ticks']['guideColor'] ?? '#94a3b8';
        $guideWidth = (float) ($theme['ticks']['guideWidth'] ?? 0.75);
        $guideDash = $theme['ticks']['guideDash'] ?? '2,2';

        $degFontSize = (float) ($ringConfig['degreeFontSize'] ?? 12);
        $minFontSize = max(6.5, $degFontSize - 2.0);
        $degOffset = (float) ($ringConfig['degreeOffset'] ?? 24);
        $degMinSpace = (float) ($ringConfig['degreeMinuteSpace'] ?? 3);

        $isOutwardLabels = ($ringConfig['labelDirection'] ?? 'inward') === 'outward';
        $dir = $isOutwardLabels ? 1.0 : -1.0;

        $degRadius = $baseRadius + ($dir * $degOffset);
        $minRadius = $degRadius + ($dir * ($degFontSize + $degMinSpace));

        $minSeparation = (float) ($ringConfig['minSeparationDeg'] ?? ($ringKey === 'outer' ? 5.5 : 8.5));
        $guideAnchorOffset = (float) ($ringConfig['guideAnchorOffset'] ?? ($ticksInnerR > $baseRadius ? 14.0 : -14.0));

        $planetGlyphs = $this->getDefaultPlanetGlyphs();

        $items = [];
        foreach ($planets as $name => $p) {
            if (!isset($p['longitude'])) {
                continue;
            }

            $exactAngle = $this->toDisplayAngle((float) $p['longitude'], $ascLon);
            $degInSign = fmod(fmod((float) $p['longitude'], 30.0) + 30.0, 30.0);
            $d = (int) floor($degInSign);
            $m = (int) round(($degInSign - $d) * 60);
            if ($m >= 60) {
                $d++;
                $m = 0;
            }

            $items[] = [
                'name' => $name,
                'data' => $p,
                'exactAngle' => $exactAngle,
                'renderAngle' => $exactAngle,
                'degOnly' => "{$d}°",
                'minOnly' => sprintf("%02d'", $m),
            ];
        }

        $items = $this->resolveAngularCollisions($items, $minSeparation);

        $ringLabel = $ringKey === 'outer' ? 'Externo' : 'Interno';
        $output = '';

        foreach ($items as $item) {
            $name = $item['name'];
            $p = $item['data'];
            $exactAngle = $item['exactAngle'];
            $renderAngle = $item['renderAngle'];

            $tickInner = $this->polarToCartesian($exactAngle, $ticksInnerR, $cx, $cy);
            $tickOuter = $this->polarToCartesian($exactAngle, $ticksOuterR, $cx, $cy);

            $namePt = AstrologyTranslator::translate($name);
            $formattedPos = AstrologyTranslator::translateFormatted($p['formatted'] ?? '');

            $escapedExactTitle = htmlspecialchars(
                "{$ringLabel} — {$namePt}: {$formattedPos} (Posição Exata)",
                ENT_QUOTES | ENT_XML1,
                'UTF-8'
            );

            $output .= "    <line x1=\"{$tickInner['x']}\" y1=\"{$tickInner['y']}\" x2=\"{$tickOuter['x']}\" y2=\"{$tickOuter['y']}\" "
                    . "stroke=\"{$tickColor}\" stroke-width=\"{$tickWidth}\">\n";
            $output .= "      <title>{$escapedExactTitle}</title>\n";
            $output .= "    </line>\n";

            $angleDisplacement = abs(fmod($renderAngle - $exactAngle + 540.0, 360.0) - 180.0);
            if ($angleDisplacement > 0.8) {
                $tickIsOutsidePlanet = $ticksInnerR > $baseRadius;
                $tickStart = $tickIsOutsidePlanet ? $tickInner : $tickOuter;

                $labelsBetweenTickAndPlanet = ($tickIsOutsidePlanet && $isOutwardLabels) || (!$tickIsOutsidePlanet && !$isOutwardLabels);
                $guideTargetRadius = $labelsBetweenTickAndPlanet
                    ? ($tickIsOutsidePlanet ? $minRadius + 7.0 : $minRadius - 7.0)
                    : ($baseRadius + $guideAnchorOffset);

                $symEdge = $this->polarToCartesian($renderAngle, $guideTargetRadius, $cx, $cy);
                $output .= "    <line x1=\"{$tickStart['x']}\" y1=\"{$tickStart['y']}\" x2=\"{$symEdge['x']}\" y2=\"{$symEdge['y']}\" "
                        . "stroke=\"{$guideColor}\" stroke-width=\"{$guideWidth}\" stroke-dasharray=\"{$guideDash}\" stroke-opacity=\"0.6\" />\n";
            }

            $pos = $this->polarToCartesian($renderAngle, $baseRadius, $cx, $cy);
            $degPos = $this->polarToCartesian($renderAngle, $degRadius, $cx, $cy);
            $minPos = $this->polarToCartesian($renderAngle, $minRadius, $cx, $cy);

            $cleanName = strtolower(str_replace(' ', '_', $name));
            $iconFile = $themeDir . '/' . ($theme['assets']['planetsDir'] ?? 'planets/') . $cleanName . '.' . $ext;
            $iconDataUri = ChartSvgBuilder::fileToDataUri($iconFile);

            $rx = !empty($p['is_retrograde']) ? ' (R)' : '';
            $escapedTitle = htmlspecialchars("{$ringLabel} — {$namePt}: {$formattedPos}{$rx}", ENT_QUOTES | ENT_XML1, 'UTF-8');

            $rxFontSize = (float) ($ringConfig['retrogradeFontSize'] ?? 10);
            $rxRight = (float) ($ringConfig['retrogradeRightGap'] ?? 8);
            $rxTop = (float) ($ringConfig['retrogradeTopGap'] ?? -10);

            $output .= "    <g transform=\"translate({$pos['x']}, {$pos['y']})\">\n";
            $output .= "      <title>{$escapedTitle}</title>\n";

            if ($iconDataUri) {
                $output .= "      <image href=\"{$iconDataUri}\" x=\"-" . ($pWidth / 2) . "\" y=\"-" . ($pHeight / 2) . "\" width=\"{$pWidth}\" height=\"{$pHeight}\" />\n";
            } else {
                $glyph = $planetGlyphs[$name]['symbol'] ?? substr($name, 0, 2);
                $color = $planetGlyphs[$name]['color'] ?? '#38bdf8';
                $escapedGlyph = htmlspecialchars($glyph, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $output .= "      <circle cx=\"0\" cy=\"0\" r=\"11\" fill=\"#ffffff\" stroke=\"{$color}\" stroke-width=\"1.5\" />\n";
                $output .= "      <text x=\"0\" y=\"1\" font-size=\"12\" font-family=\"sans-serif\" font-weight=\"bold\" fill=\"{$color}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$escapedGlyph}</text>\n";
            }

            if (!empty($p['is_retrograde'])) {
                $output .= "      <text x=\"{$rxRight}\" y=\"{$rxTop}\" font-size=\"{$rxFontSize}\" font-family=\"sans-serif\" font-weight=\"bold\" fill=\"#ef4444\">R</text>\n";
            }
            $output .= "    </g>\n";

            $escapedDegOnly = htmlspecialchars($item['degOnly'], ENT_QUOTES | ENT_XML1, 'UTF-8');
            $escapedMinOnly = htmlspecialchars($item['minOnly'], ENT_QUOTES | ENT_XML1, 'UTF-8');

            $degColor = $ringKey === 'outer' ? '#1e293b' : '#334155';

            $output .= "    <text x=\"{$degPos['x']}\" y=\"{$degPos['y']}\" "
                    . "font-size=\"{$degFontSize}\" font-family=\"sans-serif\" font-weight=\"600\" fill=\"{$degColor}\" text-anchor=\"middle\" dominant-baseline=\"central\">\n";
            $output .= "      {$escapedDegOnly}\n";
            $output .= "    </text>\n";

            $output .= "    <text x=\"{$minPos['x']}\" y=\"{$minPos['y']}\" "
                    . "font-size=\"{$minFontSize}\" font-family=\"sans-serif\" font-weight=\"500\" fill=\"#64748b\" text-anchor=\"middle\" dominant-baseline=\"central\">\n";
            $output .= "      {$escapedMinOnly}\n";
            $output .= "    </text>\n";
        }

        return $output;
    }

    protected function renderOuterAngleMarkers(array $angles, float $ascLon, float $cx, float $cy): string
    {
        $shortLabels = [
            'Ascendant' => 'AC',
            'MC' => 'MC',
        ];

        $output = '';

        foreach ($shortLabels as $key => $abbr) {
            if (!isset($angles[$key]['longitude'])) {
                continue;
            }

            $ang = $this->toDisplayAngle((float) $angles[$key]['longitude'], $ascLon);
            $p1 = $this->polarToCartesian($ang, 464, $cx, $cy);
            $p2 = $this->polarToCartesian($ang, 474, $cx, $cy);
            $lbl = $this->polarToCartesian($ang, 484, $cx, $cy);

            $title = htmlspecialchars("Externo {$abbr}: " . ($angles[$key]['formatted'] ?? ''), ENT_QUOTES | ENT_XML1, 'UTF-8');

            $output .= "    <g>\n";
            $output .= "      <title>{$title}</title>\n";
            $output .= "      <line x1=\"{$p1['x']}\" y1=\"{$p1['y']}\" x2=\"{$p2['x']}\" y2=\"{$p2['y']}\" stroke=\"#db2777\" stroke-width=\"2\" />\n";
            $output .= "      <text x=\"{$lbl['x']}\" y=\"{$lbl['y']}\" font-size=\"10\" font-weight=\"bold\" font-family=\"sans-serif\" fill=\"#db2777\" text-anchor=\"middle\" dominant-baseline=\"central\">{$abbr}</text>\n";
            $output .= "    </g>\n";
        }

        return $output;
    }

    protected function renderOuterHouseTicks(array $houses, float $ascLon, float $cx, float $cy): string
    {
        $output = '';

        for ($i = 1; $i <= 12; $i++) {
            if (!isset($houses[$i]['longitude'])) {
                continue;
            }

            $ang = $this->toDisplayAngle((float) $houses[$i]['longitude'], $ascLon);
            $p1 = $this->polarToCartesian($ang, 466, $cx, $cy);
            $p2 = $this->polarToCartesian($ang, 474, $cx, $cy);
            $lbl = $this->polarToCartesian($ang + 2.0, 481, $cx, $cy);

            $output .= "    <line x1=\"{$p1['x']}\" y1=\"{$p1['y']}\" x2=\"{$p2['x']}\" y2=\"{$p2['y']}\" stroke=\"#94a3b8\" stroke-width=\"1\" stroke-dasharray=\"2,2\" />\n";
            $output .= "    <text x=\"{$lbl['x']}\" y=\"{$lbl['y']}\" font-size=\"8\" font-family=\"sans-serif\" fill=\"#94a3b8\" text-anchor=\"middle\" dominant-baseline=\"central\">{$i}</text>\n";
        }

        return $output;
    }

    protected function renderEmptyState(): string
    {
        return "<svg viewBox=\"0 0 600 200\" xmlns=\"http://www.w3.org/2000/svg\" class=\"natal-chart-svg natal-chart-empty-state\">\n"
            . "  <rect width=\"100%\" height=\"100%\" fill=\"#f8fafc\" rx=\"8\" />\n"
            . "  <text x=\"300\" y=\"105\" font-size=\"14\" font-family=\"sans-serif\" fill=\"#64748b\" text-anchor=\"middle\">Selecione dois mapas completos para gerar a sobreposição.</text>\n"
            . "</svg>";
    }
}
