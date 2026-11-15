<?php

namespace Plugins\Astrology\Services;

use Plugins\Astrology\DTOs\ChartLayerData;

class CrossAspectCalculationService
{
    protected array $aspectDefinitions = [
        'conjunction' => [
            'label' => 'Conjunção',
            'angle' => 0.0,
            'symbol' => '☌',
            'category' => 'major',
        ],
        'opposition' => [
            'label' => 'Oposição',
            'angle' => 180.0,
            'symbol' => '☍',
            'category' => 'major',
        ],
        'trine' => [
            'label' => 'Trígono',
            'angle' => 120.0,
            'symbol' => '△',
            'category' => 'major',
        ],
        'square' => [
            'label' => 'Quadratura',
            'angle' => 90.0,
            'symbol' => '□',
            'category' => 'major',
        ],
        'sextile' => [
            'label' => 'Sextil',
            'angle' => 60.0,
            'symbol' => '⚹',
            'category' => 'major',
        ],
        'quincunx' => [
            'label' => 'Quincúncio',
            'angle' => 150.0,
            'symbol' => '⚻',
            'category' => 'minor',
        ],
        'semisquare' => [
            'label' => 'Semiquadratura',
            'angle' => 45.0,
            'symbol' => '∠',
            'category' => 'minor',
        ],
        'sesquiquadrate' => [
            'label' => 'Sesquiquadratura',
            'angle' => 135.0,
            'symbol' => '⚼',
            'category' => 'minor',
        ],
        'semisextile' => [
            'label' => 'Semisextil',
            'angle' => 30.0,
            'symbol' => '⚺',
            'category' => 'minor',
        ],
    ];

    protected array $luminaries = ['Sun', 'Moon'];

    public function calculate(ChartLayerData $inner, ChartLayerData $outer, array $options = []): array
    {
        $orbGeneral = (float) ($options['orb_general'] ?? 6.0);
        $orbLuminaries = (float) ($options['orb_luminaries'] ?? 8.0);
        $orbMinor = (float) ($options['orb_minor'] ?? min($orbGeneral * 0.4, 2.0));
        $includeMinors = (bool) ($options['include_minors'] ?? false);
        $includeInnerAngles = (bool) ($options['include_inner_angles'] ?? true);
        $includeOuterAngles = (bool) ($options['include_outer_angles'] ?? true);
        $excludedPoints = $options['excluded_points'] ?? ['South Node'];
        $applyingMode = $options['applying_mode'] ?? 'none';

        $innerPoints = $this->collectLayerPoints($inner, $includeInnerAngles, $excludedPoints);
        $outerPoints = $this->collectLayerPoints($outer, $includeOuterAngles, $excludedPoints);

        $aspects = [];

        foreach ($innerPoints as $p1Name => $p1Data) {
            if (!isset($p1Data['longitude'])) {
                continue;
            }
            $lon1 = (float) $p1Data['longitude'];
            $speed1 = (float) ($p1Data['daily_speed'] ?? 0.0);

            foreach ($outerPoints as $p2Name => $p2Data) {
                if (!isset($p2Data['longitude'])) {
                    continue;
                }
                $lon2 = (float) $p2Data['longitude'];
                $speed2 = (float) ($p2Data['daily_speed'] ?? 0.0);

                $actualAngle = $this->angularDistance($lon1, $lon2);
                $hasLuminary = in_array($p1Name, $this->luminaries, true) || in_array($p2Name, $this->luminaries, true);
                $majorMaxOrb = $hasLuminary ? $orbLuminaries : $orbGeneral;

                $bestMatch = null;

                foreach ($this->aspectDefinitions as $type => $def) {
                    if ($def['category'] === 'minor' && !$includeMinors) {
                        continue;
                    }

                    $allowedOrb = ($def['category'] === 'minor') ? $orbMinor : $majorMaxOrb;
                    $orb = abs($actualAngle - $def['angle']);

                    if ($orb <= $allowedOrb) {
                        if ($bestMatch === null || $orb < $bestMatch['orb']) {
                            $isApplying = $this->resolveApplyingState(
                                $lon1,
                                $speed1,
                                $lon2,
                                $speed2,
                                $def['angle'],
                                $orb,
                                $applyingMode
                            );

                            $bestMatch = [
                                'point1' => $p1Name,
                                'point2' => $p2Name,
                                'aspect' => $def['label'],
                                'aspect_type' => $type,
                                'symbol' => $def['symbol'],
                                'category' => $def['category'],
                                'target_angle' => $def['angle'],
                                'actual_angle' => round($actualAngle, 4),
                                'orb' => round($orb, 4),
                                'orb_formatted' => $this->formatOrb($orb),
                                'is_applying' => $isApplying,
                                'state' => $isApplying === null ? null : ($isApplying ? 'Aplicativo' : 'Separativo'),
                            ];
                        }
                    }
                }

                if ($bestMatch !== null) {
                    $aspects[] = $bestMatch;
                }
            }
        }

        usort($aspects, fn (array $a, array $b) => $a['orb'] <=> $b['orb']);

        return $aspects;
    }

    public function calculateHouseOverlays(ChartLayerData $inner, ChartLayerData $outer): array
    {
        if (count($inner->houses) < 12) {
            return [];
        }

        $overlays = [];
        $outerPoints = array_merge($outer->planets, $outer->angles);

        foreach ($outerPoints as $name => $point) {
            if (!isset($point['longitude'])) {
                continue;
            }

            $house = $this->findHouseForLongitude((float) $point['longitude'], $inner->houses);
            if ($house !== null) {
                $overlays[$name] = $house;
            }
        }

        return $overlays;
    }

    protected function collectLayerPoints(ChartLayerData $layer, bool $includeAngles, array $excludedPoints): array
    {
        $points = $layer->planets;

        if ($includeAngles) {
            $points = array_merge($points, $layer->angles);
        }

        foreach ($excludedPoints as $excluded) {
            unset($points[$excluded]);
        }

        return $points;
    }

    protected function angularDistance(float $lon1, float $lon2): float
    {
        $diff = abs(fmod($lon1 - $lon2, 360.0));
        if ($diff > 180.0) {
            $diff = 360.0 - $diff;
        }

        return $diff;
    }

    protected function resolveApplyingState(
        float $lon1,
        float $speed1,
        float $lon2,
        float $speed2,
        float $targetAngle,
        float $currentOrb,
        string $applyingMode
    ): ?bool {
        if ($applyingMode === 'none') {
            return null;
        }

        $effectiveSpeed1 = in_array($applyingMode, ['both', 'inner_only'], true) ? $speed1 : 0.0;
        $effectiveSpeed2 = in_array($applyingMode, ['both', 'outer_only'], true) ? $speed2 : 0.0;

        if ($effectiveSpeed1 == 0.0 && $effectiveSpeed2 == 0.0) {
            return null;
        }

        $dt = 0.001;
        $nextAngle = $this->angularDistance(
            $lon1 + ($effectiveSpeed1 * $dt),
            $lon2 + ($effectiveSpeed2 * $dt)
        );
        $nextOrb = abs($nextAngle - $targetAngle);

        return $nextOrb < $currentOrb;
    }

    protected function findHouseForLongitude(float $longitude, array $houses): ?int
    {
        $lon = fmod(fmod($longitude, 360.0) + 360.0, 360.0);

        for ($h = 1; $h <= 12; $h++) {
            $next = ($h === 12) ? 1 : ($h + 1);
            if (!isset($houses[$h]['longitude'], $houses[$next]['longitude'])) {
                continue;
            }

            $cusp = fmod(fmod((float) $houses[$h]['longitude'], 360.0) + 360.0, 360.0);
            $nextCusp = fmod(fmod((float) $houses[$next]['longitude'], 360.0) + 360.0, 360.0);

            $arcSize = fmod($nextCusp - $cusp + 360.0, 360.0);
            $offset = fmod($lon - $cusp + 360.0, 360.0);

            if ($arcSize > 0 && $offset < $arcSize) {
                return $h;
            }
        }

        return null;
    }

    protected function formatOrb(float $orb): string
    {
        $deg = (int) floor($orb);
        $min = (int) round(($orb - $deg) * 60);

        if ($min === 60) {
            $deg++;
            $min = 0;
        }

        return sprintf("%d°%02d'", $deg, $min);
    }
}
