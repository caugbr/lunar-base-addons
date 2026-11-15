<?php

namespace Plugins\Astrology\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Plugins\Astrology\DTOs\BiwheelChartData;
use Plugins\Astrology\DTOs\ChartLayerData;
use Plugins\Astrology\Models\NatalChart;
use Plugins\Astrology\Services\BiwheelChartSvgBuilder;
use Plugins\Astrology\Services\ChartCalculationService;
use Plugins\Astrology\Services\CrossAspectCalculationService;
use Plugins\Astrology\Services\ThemeManager;
use Throwable;

class BiwheelChartController extends Controller
{
    public function index(
        Request $request,
        ChartCalculationService $calculationService,
        CrossAspectCalculationService $aspectService,
        BiwheelChartSvgBuilder $biwheelBuilder
    ) {
        $charts = NatalChart::where('status', 'completed')
            ->orderBy('name')
            ->get();

        $state = $this->resolveRequestState($request, $charts);
        $compiledSvg = '';
        $solarReturnMeta = null;
        $calculationError = null;

        if ($state['innerChart']) {
            try {
                $result = $this->buildOutput(
                    $state,
                    $calculationService,
                    $aspectService,
                    $biwheelBuilder
                );

                $compiledSvg = $result['svg'];
                $solarReturnMeta = $result['solarReturnMeta'];
            } catch (Throwable $e) {
                $calculationError = $e->getMessage();
            }
        }

        $isSingleView = $state['outerType'] === 'solar_return' && $state['srDisplay'] === 'single';
        $availableThemes = ThemeManager::getBiwheelThemes();

        return view('astrology::admin.biwheel.index', array_merge($state, [
            'charts'           => $charts,
            'availableThemes'  => $availableThemes,
            'compiledSvg'      => $compiledSvg,
            'solarReturnMeta'  => $solarReturnMeta,
            'calculationError' => $calculationError,
            'isSingleView'     => $isSingleView,
        ]));
    }

    public function downloadSvg(
        Request $request,
        ChartCalculationService $calculationService,
        CrossAspectCalculationService $aspectService,
        BiwheelChartSvgBuilder $biwheelBuilder
    ) {
        $charts = NatalChart::where('status', 'completed')->get();
        $state = $this->resolveRequestState($request, $charts);

        abort_unless($state['innerChart'], 404, 'Mapa base não encontrado.');

        $result = $this->buildOutput(
            $state,
            $calculationService,
            $aspectService,
            $biwheelBuilder
        );

        $baseSlug = $state['innerChart']->slug;
        $filename = match ($state['outerType']) {
            'transit'      => "transitos-{$baseSlug}-{$state['transitDate']}-{$state['themeSlug']}.svg",
            'solar_return' => "revolucao-solar-{$baseSlug}-{$state['srYear']}-{$state['themeSlug']}.svg",
            default        => "biwheel-{$baseSlug}-" . ($state['outerChart']?->slug ?? 'mapa') . "-{$state['themeSlug']}.svg",
        };

        return response($result['svg'], 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    protected function resolveRequestState(Request $request, $charts): array
    {
        $innerId = $request->query('inner_id', $charts->first()?->id);
        $innerChart = $innerId ? $charts->firstWhere('id', (int) $innerId) : null;

        $outerType = $request->query('outer_type', 'chart');
        if (!in_array($outerType, ['chart', 'transit', 'solar_return'], true)) {
            $outerType = 'chart';
        }

        $outerId = $request->query('outer_id', $charts->skip(1)->first()?->id ?? $charts->first()?->id);
        $outerChart = ($outerType === 'chart' && $outerId)
            ? $charts->firstWhere('id', (int) $outerId)
            : null;

        $defaultTz = $innerChart?->timezone ?? 'America/Sao_Paulo';
        $nowInTz = Carbon::now($defaultTz);

        $defaultOrbGeneral = $outerType === 'transit' ? 2.5 : 6.0;
        $defaultOrbLuminaries = $outerType === 'transit' ? 4.0 : 8.0;
        $defaultOuterAngles = $outerType !== 'transit';

        return [
            'innerChart'         => $innerChart,
            'outerChart'         => $outerChart,
            'outerType'          => $outerType,
            'themeSlug'          => (string) $request->query('theme', 'classic'),
            'invert'             => $request->has('invert') ? $request->boolean('invert') : ($outerType === 'solar_return'),
            'orbGeneral'         => (float) $request->query('orb_general', $defaultOrbGeneral),
            'orbLuminaries'      => (float) $request->query('orb_luminaries', $defaultOrbLuminaries),
            'includeMinors'      => $request->boolean('include_minors', false),
            'includeOuterAngles' => $request->has('include_outer_angles')
                ? $request->boolean('include_outer_angles')
                : $defaultOuterAngles,

            // Campos efemeros de Transito
            'transitDate'      => (string) $request->query('transit_date', $nowInTz->format('Y-m-d')),
            'transitTime'      => (string) $request->query('transit_time', $nowInTz->format('H:i')),
            'transitTimezone'  => (string) $request->query('transit_timezone', $defaultTz),
            'transitCity'      => (string) $request->query('transit_city', $innerChart?->city ?? ''),
            'transitLatitude'  => (float) $request->query('transit_latitude', $innerChart?->latitude ?? -23.5505),
            'transitLongitude' => (float) $request->query('transit_longitude', $innerChart?->longitude ?? -46.6333),

            // Campos efemeros de Revolucao Solar
            'srYear'        => (int) $request->query('sr_year', $nowInTz->format('Y')),
            'srDisplay'     => in_array($request->query('sr_display'), ['biwheel', 'single'], true)
                ? $request->query('sr_display')
                : 'biwheel',
            'srHouseSystem' => (string) $request->query('sr_house_system', $innerChart?->house_system ?? 'placidus'),
            'srTimezone'    => (string) $request->query('sr_timezone', $defaultTz),
            'srCity'        => (string) $request->query('sr_city', $innerChart?->city ?? ''),
            'srLatitude'    => (float) $request->query('sr_latitude', $innerChart?->latitude ?? -23.5505),
            'srLongitude'   => (float) $request->query('sr_longitude', $innerChart?->longitude ?? -46.6333),
        ];
    }

    protected function buildOutput(
        array $state,
        ChartCalculationService $calculationService,
        CrossAspectCalculationService $aspectService,
        BiwheelChartSvgBuilder $biwheelBuilder
    ): array {
        /** @var NatalChart $innerChart */
        $innerChart = $state['innerChart'];
        $innerLayer = ChartLayerData::fromNatalChart($innerChart);
        $solarReturnMeta = null;

        if ($state['outerType'] === 'transit') {
            $transitData = $calculationService->calculateEphemeral([
                'date'         => $state['transitDate'],
                'time'         => $state['transitTime'],
                'timezone'     => $state['transitTimezone'],
                'latitude'     => $state['transitLatitude'],
                'longitude'    => $state['transitLongitude'],
                'house_system' => $innerChart->house_system,
                'aspects'      => false,
            ]);

            $formattedDate = Carbon::parse($state['transitDate'])->format('d/m/Y');
            $loc = $state['transitCity'] ?: "{$state['transitLatitude']}, {$state['transitLongitude']}";
            $outerLayer = ChartLayerData::fromCalculatedData(
                chartData: $transitData,
                label: 'Trânsitos',
                subtitle: "{$formattedDate} às {$state['transitTime']} ({$state['transitTimezone']}) — {$loc}",
                houseSystem: $innerChart->house_system,
                timezone: $state['transitTimezone'],
            );

            $applyingMode = $state['invert'] ? 'inner_only' : 'outer_only';
        } elseif ($state['outerType'] === 'solar_return') {
            $srData = $calculationService->calculateSolarReturn($innerChart, $state['srYear'], [
                'latitude'       => $state['srLatitude'],
                'longitude'      => $state['srLongitude'],
                'timezone'       => $state['srTimezone'],
                'house_system'   => $state['srHouseSystem'],
                'include_minors' => $state['includeMinors'],
                'orb_general'    => $state['orbGeneral'],
                'orb_luminaries' => $state['orbLuminaries'],
            ]);

            $solarReturnMeta = $srData['solar_return'] ?? [];
            $localDt = !empty($solarReturnMeta['local_datetime'])
                ? Carbon::parse($solarReturnMeta['local_datetime'])
                : Carbon::now($state['srTimezone']);

            $loc = $state['srCity'] ?: "{$state['srLatitude']}, {$state['srLongitude']}";

            $outerLayer = ChartLayerData::fromCalculatedData(
                chartData: $srData,
                label: "Revolução Solar {$state['srYear']}",
                subtitle: $localDt->format('d/m/Y \à\s H:i:s') . " ({$state['srTimezone']}) — {$loc}",
                houseSystem: $state['srHouseSystem'],
                timezone: $state['srTimezone'],
            );

            $applyingMode = 'none';
        } else {
            if (!$state['outerChart']) {
                return ['svg' => '', 'solarReturnMeta' => null];
            }
            $outerLayer = ChartLayerData::fromNatalChart($state['outerChart']);
            $applyingMode = 'none';
        }

        if ($state['invert']) {
            [$innerLayer, $outerLayer] = [$outerLayer, $innerLayer];
        }

        $crossAspects = $aspectService->calculate($innerLayer, $outerLayer, [
            'orb_general'          => $state['orbGeneral'],
            'orb_luminaries'       => $state['orbLuminaries'],
            'include_minors'       => $state['includeMinors'],
            'include_outer_angles' => $state['includeOuterAngles'],
            'applying_mode'        => $applyingMode,
        ]);

        $houseOverlays = $aspectService->calculateHouseOverlays($innerLayer, $outerLayer);

        $biwheelData = new BiwheelChartData(
            inner: $innerLayer,
            outer: $outerLayer,
            crossAspects: $crossAspects,
            houseOverlays: $houseOverlays,
        );

        return [
            'svg'             => $biwheelBuilder->build($biwheelData, $state['themeSlug']),
            'solarReturnMeta' => $solarReturnMeta,
        ];
    }
}
