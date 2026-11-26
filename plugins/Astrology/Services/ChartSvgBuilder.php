<?php

namespace Plugins\Astrology\Services;

use Plugins\Astrology\Models\NatalChart;
use Plugins\Astrology\Services\Concerns\RendersMandalaPrimitives;

class ChartSvgBuilder
{
    use RendersMandalaPrimitives;

    protected static array $dataUriCache = [];

    public static function fileToDataUri(string $filePath): string
    {
        if (isset(self::$dataUriCache[$filePath])) {
            return self::$dataUriCache[$filePath];
        }

        if (!file_exists($filePath)) {
            return '';
        }

        $mime = mime_content_type($filePath);
        if (!$mime || $mime === 'text/plain') {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                default => 'application/octet-stream',
            };
        }

        $base64 = base64_encode(file_get_contents($filePath));

        return self::$dataUriCache[$filePath] = "data:{$mime};base64,{$base64}";
    }

    public static function resolveFonts(array $theme): array
    {
        $typography = $theme['typography'] ?? [];

        $body = trim((string) ($typography['fontFamily'] ?? 'sans-serif')) ?: 'sans-serif';
        $heading = trim((string) ($typography['headingFontFamily'] ?? $body)) ?: $body;
        $mono = trim((string) ($typography['monoFontFamily'] ?? 'monospace')) ?: 'monospace';

        return [
            'body'    => htmlspecialchars($body, ENT_QUOTES | ENT_XML1, 'UTF-8'),
            'heading' => htmlspecialchars($heading, ENT_QUOTES | ENT_XML1, 'UTF-8'),
            'mono'    => htmlspecialchars($mono, ENT_QUOTES | ENT_XML1, 'UTF-8'),
        ];
    }

    public static function renderFontStyleBlock(array $theme): string
    {
        $googleFontsUrl = trim((string) ($theme['typography']['googleFontsUrl'] ?? ''));
        if ($googleFontsUrl === '') {
            return '';
        }

        // Escapa '&' para '&amp;' evitando erro de parser XML ao baixar o SVG
        $safeUrl = htmlspecialchars($googleFontsUrl, ENT_NOQUOTES | ENT_XML1, 'UTF-8');
        $safeUrl = str_replace("'", '%27', $safeUrl);

        return "    <style type=\"text/css\">\n"
             . "      @import url('{$safeUrl}');\n"
             . "    </style>\n";
    }

    public function build(NatalChart $chart, ?string $themeSlug = 'classic'): string
    {
        if (!$chart->isCompleted() || empty($chart->chart_data)) {
            return $this->renderEmptyState($chart);
        }

        $theme = ThemeManager::getTheme($themeSlug ?? 'classic');
        $themeDir = ThemeManager::getThemeDirectory($theme['slug']) ?? ($theme['path'] ?? '');

        $chartData = $chart->chart_data;
        $planets = $chartData['planets'] ?? [];
        $angles = $chartData['angles'] ?? [];
        $houses = $chartData['houses'] ?? [];
        $aspects = $chartData['aspects'] ?? [];

        $ascendantLon = (float) ($angles['Ascendant']['longitude'] ?? 0);

        $vbWidth = (int) ($theme['canvas']['viewBoxWidth'] ?? 1000);
        $vbHeight = (int) ($theme['canvas']['viewBoxHeight'] ?? 1414);
        $bgColor = $theme['canvas']['backgroundColor'] ?? '#ffffff';

        $bgAsset = $theme['assets']['background'] ?? null;
        $bgDataUri = $bgAsset ? self::fileToDataUri($themeDir . '/' . $bgAsset) : '';

        $baseFile = $themeDir . '/' . ($theme['assets']['base'] ?? 'base.svg');
        $baseDataUri = self::fileToDataUri($baseFile);

        $svg = "<svg viewBox=\"0 0 {$vbWidth} {$vbHeight}\" xmlns=\"http://www.w3.org/2000/svg\" class=\"natal-chart-svg\">\n";
        $svg .= "  <defs>\n";
        $svg .= self::renderFontStyleBlock($theme);
        $svg .= "    <filter id=\"shadow\" x=\"-20%\" y=\"-20%\" width=\"140%\" height=\"140%\">\n";
        $svg .= "      <feDropShadow dx=\"0\" dy=\"1\" stdDeviation=\"2\" flood-color=\"#000000\" flood-opacity=\"0.4\"/>\n";
        $svg .= "    </filter>\n";
        $svg .= "  </defs>\n";

        if ($bgColor && $bgColor !== 'transparent') {
            $escapedBg = htmlspecialchars($bgColor, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $svg .= "  <rect width=\"{$vbWidth}\" height=\"{$vbHeight}\" fill=\"{$escapedBg}\" />\n";
        }

        if ($bgDataUri) {
            $svg .= "  <image href=\"{$bgDataUri}\" x=\"0\" y=\"0\" width=\"{$vbWidth}\" height=\"{$vbHeight}\" />\n";
        }

        $svg .= $this->renderHeader($chart, $theme, $themeDir);

        $wheelConfig = $theme['layout']['wheel'] ?? [];
        $targetCx = (float) ($wheelConfig['centerX'] ?? ($vbWidth / 2));
        $targetCy = (float) ($wheelConfig['centerY'] ?? 530);

        // Padrão com margem de respiro ao redor da mandala (80% do menor lado do papel)
        $defaultSize = min($vbWidth, $vbHeight) * 0.8;

        if (isset($wheelConfig['margin'])) {
            $defaultSize = max(100, min($vbWidth, $vbHeight) - ((float) $wheelConfig['margin'] * 2.0));
        } elseif (
            isset($wheelConfig['scale'])
            && !isset($wheelConfig['width'], $wheelConfig['height'], $wheelConfig['size'])
        ) {
            // Compatibilidade opcional caso algum tema antigo ainda defina apenas "scale"
            $defaultSize = 1000.0 * (float) $wheelConfig['scale'];
        }

        $baseWidth  = (float) ($wheelConfig['width']  ?? $wheelConfig['size'] ?? $defaultSize);
        $baseHeight = (float) ($wheelConfig['height'] ?? $wheelConfig['size'] ?? $defaultSize);

        // Centraliza apenas a imagem da mandala em (targetCx, targetCy)
        $baseX = $targetCx - ($baseWidth / 2.0);
        $baseY = $targetCy - ($baseHeight / 2.0);

        $svg .= "  <!-- Mandala Central -->\n";
        $svg .= "  <g id=\"mandala-wheel\">\n";

        if (!empty($theme['bands']) && is_array($theme['bands'])) {
            $svg .= "    <g class=\"chart-bands\">\n";
            $svg .= $this->renderBands($theme['bands'], $ascendantLon, $targetCx, $targetCy);
            $svg .= "    </g>\n";
        }

        if ($baseDataUri) {
            $svg .= "    <image href=\"{$baseDataUri}\" x=\"{$baseX}\" y=\"{$baseY}\" width=\"{$baseWidth}\" height=\"{$baseHeight}\" preserveAspectRatio=\"xMidYMid meet\" />\n";
        }

        $svg .= "    <g class=\"chart-signs\">\n";
        $svg .= $this->renderSigns($ascendantLon, $theme, $themeDir, $targetCx, $targetCy);
        $svg .= "    </g>\n";

        $svg .= "    <g class=\"chart-houses\">\n";
        $svg .= $this->renderHouseLines($houses, $ascendantLon, $theme, $targetCx, $targetCy);
        $svg .= "    </g>\n";

        if (!empty($theme['axes']['showMarkers'])) {
            $svg .= "    <g class=\"chart-axes\">\n";
            $svg .= $this->renderAxisMarkers($angles, $houses, $ascendantLon, $theme, $themeDir, $targetCx, $targetCy);
            $svg .= "    </g>\n";
        }

        $svg .= "    <g class=\"chart-aspects\">\n";
        $svg .= $this->renderAspectLines($aspects, $planets, $angles, $ascendantLon, $theme, $targetCx, $targetCy);
        $svg .= "    </g>\n";

        $svg .= "    <g class=\"chart-planets\">\n";
        $svg .= $this->renderPlanets($planets, $ascendantLon, $theme, $themeDir, $targetCx, $targetCy);
        $svg .= "    </g>\n";

        $svg .= "  </g>\n";

        $gridConfig = $theme['layout']['aspectGrid'] ?? [];
        if ($gridConfig) {
            $gridX = (float) ($gridConfig['x'] ?? 50);
            $gridY = (float) ($gridConfig['y'] ?? 990);
            $gridCellSize = (float) ($gridConfig['cellSize'] ?? 24);

            $aspectGridBuilder = app(AspectGridSvgBuilder::class);
            $svg .= $aspectGridBuilder->build($chart, $theme, $gridX, $gridY, $gridCellSize);
        }

        $eqConfig = $theme['layout']['elementsQualities'] ?? [];
        if ($eqConfig) {
            $eqX = (float) ($eqConfig['x'] ?? 620);
            $eqY = (float) ($eqConfig['y'] ?? 990);

            $eqBuilder = app(ElementsQualitiesSvgBuilder::class);
            $svg .= $eqBuilder->build($chart, $theme, $eqX, $eqY);
        }

        $svg .= $this->renderFooter($theme, $vbWidth, $vbHeight);

        $svg .= "</svg>";

        return $svg;
    }

    protected function renderHeader(NatalChart $chart, array $theme, string $themeDir): string
    {
        $showInfo = !empty($theme['modules']['chartInfo']);
        $showLogo = !empty($theme['modules']['logo']);

        if (!$showInfo && !$showLogo) {
            return '';
        }

        $fonts = self::resolveFonts($theme);

        $svg = "  <!-- Cabecalho da Prancha -->\n";
        $svg .= "  <g class=\"chart-header\">\n";
        if ($showInfo) {
            $name = htmlspecialchars($chart->name, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $date = htmlspecialchars($chart->formatted_date_time, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $timezone = htmlspecialchars((string) $chart->timezone, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $location = htmlspecialchars($chart->city ?: "{$chart->latitude}, {$chart->longitude}", ENT_QUOTES | ENT_XML1, 'UTF-8');
            $houseSystem = htmlspecialchars(AstrologyTranslator::translate($chart->house_system), ENT_QUOTES | ENT_XML1, 'UTF-8');

            $chartInfo = $theme['layout']['chartInfo'] ?? [];

            $infoX = htmlspecialchars((string) ($chartInfo['x'] ?? $theme['layout']['infoX'] ?? 50), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $infoY = htmlspecialchars((string) ($chartInfo['y'] ?? $theme['layout']['infoY'] ?? 45), ENT_QUOTES | ENT_XML1, 'UTF-8');
            $align = $chartInfo['align'] ?? $theme['layout']['infoAlign'] ?? 'left';
            $fontSize = (float) ($chartInfo['fontSize'] ?? 16);
            $subFontSize = round($fontSize * 0.65, 2);
            $color = htmlspecialchars((string) ($chartInfo['color'] ?? '#1e293b'), ENT_QUOTES | ENT_XML1, 'UTF-8');

            $textAnchor = match ($align) {
                'center', 'middle' => 'middle',
                'right', 'end'     => 'end',
                default            => 'start',
            };

            $infoFooterLine = ($chart->house_system && $chart->house_system !== 'none')
                ? "{$location} | Casas: {$houseSystem}"
                : $location;

            $svg .= "    <text x=\"{$infoX}\" y=\"{$infoY}\" font-size=\"{$fontSize}\" font-family=\"{$fonts['body']}\" fill=\"{$color}\" text-anchor=\"{$textAnchor}\">\n";
            $svg .= "        <tspan x=\"{$infoX}\" dy=\"0\" font-weight=\"bold\" font-family=\"{$fonts['heading']}\">{$name}</tspan>\n";
            $svg .= "        <tspan x=\"{$infoX}\" dy=\"1.7em\" font-size=\"{$subFontSize}\" fill-opacity=\"0.8\">{$date} ({$timezone})</tspan>\n";
            $svg .= "        <tspan x=\"{$infoX}\" dy=\"1.4em\" font-size=\"{$subFontSize}\" fill-opacity=\"0.8\">{$infoFooterLine}</tspan>\n";
            $svg .= "    </text>\n";
        }

        if ($showLogo && !empty($theme['assets']['logo'])) {
            $logoPath = $themeDir . '/' . $theme['assets']['logo'];
            $logoUri = self::fileToDataUri($logoPath);

            if ($logoUri) {
                $vbWidth = (float) ($theme['canvas']['viewBoxWidth'] ?? 1000);
                $vbHeight = (float) ($theme['canvas']['viewBoxHeight'] ?? 1414);
                $logoCfg = $theme['layout']['logo'] ?? [];

                $width = (float) ($logoCfg['width'] ?? 180);
                $height = (float) ($logoCfg['height'] ?? 75);
                $logoYFrom = (string) ($logoCfg['yFrom'] ?? 'top');
                $align = (string) ($logoCfg['align'] ?? 'default');

                // Resolve X (aceita número, "center" ou porcentagem ex: "50%")
                $rawX = trim((string) ($logoCfg['x'] ?? 770));
                if ($rawX === 'center') {
                    $baseX = $vbWidth / 2.0;
                } elseif (str_ends_with($rawX, '%')) {
                    $baseX = ($vbWidth * (float) rtrim($rawX, '%')) / 100.0;
                } else {
                    $baseX = (float) $rawX;
                }

                // Resolve Y (aceita número ou porcentagem)
                $rawY = trim((string) ($logoCfg['y'] ?? 35));
                $baseY = str_ends_with($rawY, '%')
                    ? ($vbHeight * (float) rtrim($rawY, '%')) / 100.0
                    : (float) $rawY;

                // Deslocamento horizontal pelo alinhamento
                $logoX = match ($align) {
                    'center', 'middle' => $baseX - ($width / 2.0),
                    'right', 'end'     => $baseX - $width,
                    default            => $baseX,
                };

                // Se vier de baixo, subtrai também a altura (como CSS bottom)
                $logoY = ($logoYFrom === 'bottom')
                    ? ($vbHeight - $baseY - $height)
                    : $baseY;

                // Mantém o conteúdo interno alinhado junto com a caixa
                $xRatio = match ($align) {
                    'center', 'middle' => 'xMid',
                    'left', 'start'    => 'xMin',
                    default            => 'xMax',
                };
                $yRatio = ($logoYFrom === 'bottom') ? 'YMax' : 'YMin';

                $svg .= "    <image href=\"{$logoUri}\" x=\"{$logoX}\" y=\"{$logoY}\" width=\"{$width}\" height=\"{$height}\" preserveAspectRatio=\"{$xRatio}{$yRatio} meet\" />\n";
            }
        }

        $svg .= "  </g>\n";

        return $svg;
    }

    protected function renderEmptyState(NatalChart $chart): string
    {
        $message = $chart->isFailed()
            ? 'Falha no cálculo do mapa astral.'
            : 'Cálculo em processamento na fila.';

        $escapedMessage = htmlspecialchars($message, ENT_QUOTES | ENT_XML1, 'UTF-8');

        return "<svg viewBox=\"0 0 600 200\" xmlns=\"http://www.w3.org/2000/svg\" class=\"natal-chart-svg natal-chart-empty-state\">\n"
            . "  <rect width=\"100%\" height=\"100%\" fill=\"#f8fafc\" rx=\"8\" />\n"
            . "  <text x=\"300\" y=\"105\" font-size=\"14\" font-family=\"sans-serif\" fill=\"#64748b\" text-anchor=\"middle\">{$escapedMessage}</text>\n"
            . "</svg>";
    }

    protected function renderAspectLines(array $aspects, array $planets, array $angles, float $ascLon, array $theme, float $cx, float $cy): string
    {
        if (empty($theme['aspects']['showLines']) || empty($aspects)) {
            return '';
        }

        $radius = (float) ($theme['radii']['aspectsInnerRadius'] ?? 144);
        $points = array_merge($planets, $angles);

        $showAngleLines = (bool) ($theme['aspects']['showAngleLines'] ?? true);
        $showNodeLines = (bool) ($theme['aspects']['showNodeLines'] ?? true);

        $angleKeys = array_keys($angles);
        $nodeKeys = ['North Node', 'South Node', 'True Node', 'mean Node'];

        $lines = '';

        foreach ($aspects as $asp) {
            $p1Name = $asp['point1'] ?? '';
            $p2Name = $asp['point2'] ?? '';

            if (!$showAngleLines && (in_array($p1Name, $angleKeys, true) || in_array($p2Name, $angleKeys, true))) {
                continue;
            }

            if (!$showNodeLines && (in_array($p1Name, $nodeKeys, true) || in_array($p2Name, $nodeKeys, true))) {
                continue;
            }

            $p1 = $points[$p1Name] ?? null;
            $p2 = $points[$p2Name] ?? null;

            if (!$p1 || !$p2) {
                continue;
            }

            $ang1 = $this->toDisplayAngle((float) $p1['longitude'], $ascLon);
            $ang2 = $this->toDisplayAngle((float) $p2['longitude'], $ascLon);

            $pt1 = $this->polarToCartesian($ang1, $radius, $cx, $cy);
            $pt2 = $this->polarToCartesian($ang2, $radius, $cx, $cy);

            $color = $theme['aspects']['colors'][$asp['aspect_type'] ?? ''] ?? '#94a3b8';
            $isMinor = ($asp['category'] ?? '') === 'minor';
            $dash = $isMinor ? 'stroke-dasharray="3,3"' : '';
            $opacity = $isMinor ? '0.45' : '0.85';

            $p1Pt = AstrologyTranslator::translate($asp['point1']);
            $aspPt = AstrologyTranslator::translate($asp['aspect']);
            $p2Pt = AstrologyTranslator::translate($asp['point2']);

            $escapedAspectTitle = htmlspecialchars(
                "{$p1Pt} {$aspPt} {$p2Pt} ({$asp['orb_formatted']})",
                ENT_QUOTES | ENT_XML1,
                'UTF-8'
            );

            $lines .= "    <line x1=\"{$pt1['x']}\" y1=\"{$pt1['y']}\" x2=\"{$pt2['x']}\" y2=\"{$pt2['y']}\" "
                   . "stroke=\"{$color}\" stroke-width=\"{$theme['aspects']['strokeWidth']}\" "
                   . "stroke-opacity=\"{$opacity}\" {$dash}>\n";
            $lines .= "      <title>{$escapedAspectTitle}</title>\n";
            $lines .= "    </line>\n";
        }

        return $lines;
    }

    protected function renderPlanets(array $planets, float $ascLon, array $theme, string $themeDir, float $cx, float $cy): string
    {
        if (empty($planets)) {
            return '';
        }

        $fonts = self::resolveFonts($theme);
        $showDegrees = (bool) ($theme['planets']['showDegrees'] ?? true);
        $showRetrograde = (bool) ($theme['planets']['showRetrograde'] ?? true);

        $baseRadius = (float) ($theme['radii']['planetsDistance'] ?? 290);
        $pWidth = (float) ($theme['dimensions']['planetWidth'] ?? 38);
        $pHeight = (float) ($theme['dimensions']['planetHeight'] ?? 38);
        $ext = $theme['assets']['extension'] ?? 'svg';

        $ticksOuterR = (float) ($theme['radii']['ticksDistance'] ?? 325);
        $ticksLen = (float) ($theme['radii']['ticksLength'] ?? 8);
        $ticksInnerR = $ticksOuterR - $ticksLen;

        $tickColor = $theme['ticks']['color'] ?? '#0284c7';
        $tickWidth = (float) ($theme['ticks']['strokeWidth'] ?? 1.5);
        $guideColor = $theme['ticks']['guideColor'] ?? '#94a3b8';
        $guideWidth = (float) ($theme['ticks']['guideWidth'] ?? 0.75);
        $guideDash = $theme['ticks']['guideDash'] ?? '2,2';

        $degFontSize = (float) ($theme['dimensions']['planetDegreeFontSize'] ?? 14);
        $minFontSize = max(6.5, $degFontSize - 2.0);

        $degOffset = (float) ($theme['dimensions']['planetDegreeOffset'] ?? (($pHeight / 2) + ($degFontSize / 2) + 4));
        $degMinSpace = (float) ($theme['dimensions']['degreeMinuteSpace'] ?? 4);
        $degRadius = $baseRadius - $degOffset;
        $minRadius = $degRadius - ($degFontSize + $degMinSpace);

        $planetGlyphs = $this->getDefaultPlanetGlyphs();

        $items = [];
        foreach ($planets as $name => $p) {
            $exactAngle = $this->toDisplayAngle((float) $p['longitude'], $ascLon);
            $degInSign = fmod((float) $p['longitude'], 30.0);
            $d = (int) floor($degInSign);
            $m = (int) round(($degInSign - $d) * 60);
            if ($m >= 60) {
                $d++;
                $m = 0;
            }

            $items[] = [
                'name'        => $name,
                'data'        => $p,
                'exactAngle'  => $exactAngle,
                'renderAngle' => $exactAngle,
                'degOnly'     => "{$d}°",
                'minOnly'     => sprintf("%02d'", $m),
            ];
        }

        $items = $this->resolveAngularCollisions($items, 7.5);

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

            $escapedExactTitle = htmlspecialchars("{$namePt}: {$formattedPos} (Posição Exata)", ENT_QUOTES | ENT_XML1, 'UTF-8');

            $output .= "    <line x1=\"{$tickInner['x']}\" y1=\"{$tickInner['y']}\" x2=\"{$tickOuter['x']}\" y2=\"{$tickOuter['y']}\" "
                    . "stroke=\"{$tickColor}\" stroke-width=\"{$tickWidth}\">\n";
            $output .= "      <title>{$escapedExactTitle}</title>\n";
            $output .= "    </line>\n";

            $angleDisplacement = abs(fmod($renderAngle - $exactAngle + 540.0, 360.0) - 180.0);
            if ($angleDisplacement > 0.8) {
                $symEdge = $this->polarToCartesian($renderAngle, $baseRadius + 14, $cx, $cy);
                $output .= "    <line x1=\"{$tickInner['x']}\" y1=\"{$tickInner['y']}\" x2=\"{$symEdge['x']}\" y2=\"{$symEdge['y']}\" "
                        . "stroke=\"{$guideColor}\" stroke-width=\"{$guideWidth}\" stroke-dasharray=\"{$guideDash}\" stroke-opacity=\"0.6\" />\n";
            }

            $pos = $this->polarToCartesian($renderAngle, $baseRadius, $cx, $cy);

            $cleanName = strtolower(str_replace(' ', '_', $name));
            $iconFile = $themeDir . '/' . ($theme['assets']['planetsDir'] ?? 'planets/') . $cleanName . '.' . $ext;
            $iconDataUri = self::fileToDataUri($iconFile);

            $rx = !empty($p['is_retrograde']) ? ' (R)' : '';
            $title = "{$namePt}: {$formattedPos}{$rx}";
            $escapedTitle = htmlspecialchars($title, ENT_QUOTES | ENT_XML1, 'UTF-8');

            $rxFontSize = (float) ($theme['dimensions']['retrogradeFontSize'] ?? 10);
            $rxRight = (float) ($theme['dimensions']['retrogradeRightGap'] ?? 8);
            $rxTop = (float) ($theme['dimensions']['retrogradeTopGap'] ?? -10);

            $output .= "    <g transform=\"translate({$pos['x']}, {$pos['y']})\">\n";
            $output .= "      <title>{$escapedTitle}</title>\n";

            if ($iconDataUri) {
                $output .= "      <image href=\"{$iconDataUri}\" x=\"-" . ($pWidth / 2) . "\" y=\"-" . ($pHeight / 2) . "\" width=\"{$pWidth}\" height=\"{$pHeight}\" />\n";
            } else {
                $glyph = $planetGlyphs[$name]['symbol'] ?? substr($name, 0, 2);
                $color = $planetGlyphs[$name]['color'] ?? '#38bdf8';
                $escapedGlyph = htmlspecialchars($glyph, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $output .= "      <circle cx=\"0\" cy=\"0\" r=\"12\" fill=\"#ffffff\" stroke=\"{$color}\" stroke-width=\"1.5\" />\n";
                $output .= "      <text x=\"0\" y=\"1\" font-size=\"13\" font-family=\"{$fonts['body']}\" font-weight=\"bold\" fill=\"{$color}\" text-anchor=\"middle\" dominant-baseline=\"central\">{$escapedGlyph}</text>\n";
            }

            if ($showRetrograde && !empty($p['is_retrograde'])) {
                $output .= "      <text x=\"{$rxRight}\" y=\"{$rxTop}\" font-size=\"{$rxFontSize}\" font-family=\"{$fonts['body']}\" font-weight=\"bold\" fill=\"#ef4444\">R</text>\n";
            }
            $output .= "    </g>\n";

            if ($showDegrees) {
                $degPos = $this->polarToCartesian($renderAngle, $degRadius, $cx, $cy);
                $minPos = $this->polarToCartesian($renderAngle, $minRadius, $cx, $cy);

                $escapedDegOnly = htmlspecialchars($item['degOnly'], ENT_QUOTES | ENT_XML1, 'UTF-8');
                $escapedMinOnly = htmlspecialchars($item['minOnly'], ENT_QUOTES | ENT_XML1, 'UTF-8');

                $output .= "    <text x=\"{$degPos['x']}\" y=\"{$degPos['y']}\" "
                        . "font-size=\"{$degFontSize}\" font-family=\"{$fonts['body']}\" font-weight=\"600\" fill=\"#334155\" text-anchor=\"middle\" dominant-baseline=\"central\">\n";
                $output .= "      {$escapedDegOnly}\n";
                $output .= "    </text>\n";

                $output .= "    <text x=\"{$minPos['x']}\" y=\"{$minPos['y']}\" "
                        . "font-size=\"{$minFontSize}\" font-family=\"{$fonts['body']}\" font-weight=\"500\" fill=\"#64748b\" text-anchor=\"middle\" dominant-baseline=\"central\">\n";
                $output .= "      {$escapedMinOnly}\n";
                $output .= "    </text>\n";
            }
        }

        return $output;
    }
}
