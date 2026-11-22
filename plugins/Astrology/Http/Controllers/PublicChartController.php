<?php

namespace Plugins\Astrology\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Plugins\Astrology\DTOs\BiwheelChartData;
use Plugins\Astrology\DTOs\ChartLayerData;
use Plugins\Astrology\Models\NatalChart;
use Plugins\Astrology\Services\BiwheelChartSvgBuilder;
use Plugins\Astrology\Services\ChartCalculationService;
use Plugins\Astrology\Services\ChartSvgBuilder;
use Plugins\Astrology\Services\CrossAspectCalculationService;
use Plugins\Astrology\Services\ThemeManager;
use Throwable;

class PublicChartController extends Controller
{
    protected const CACHE_PREFIX = 'astrology_public_calc_';

    public function index(Request $request)
    {
        $now = Carbon::now('America/Sao_Paulo');
        $availableThemes = ThemeManager::getThemes();
        $publicLayout = $this->resolvePublicLayout();
        $defaultTheme = $this->resolvePublicTheme($availableThemes);

        $defaults = [
            'mode'              => in_array($request->query('mode'), ['natal', 'transit', 'solar_return', 'synastry'], true)
                ? $request->query('mode')
                : 'natal',
            'name'              => '',
            'birth_date'        => '',
            'birth_time'        => '12:00',
            'unknown_time'      => false,
            'city'              => '',
            'latitude'          => '',
            'longitude'         => '',
            'timezone'          => 'America/Sao_Paulo',
            'house_system'      => 'placidus',
            'theme'             => $defaultTheme,

            // Trânsitos
            'transit_date'      => $now->format('Y-m-d'),
            'transit_time'      => $now->format('H:i'),
            'transit_city'      => '',
            'transit_latitude'  => '',
            'transit_longitude' => '',
            'transit_timezone'  => '',

            // Revolução Solar (padrão: mapa isolado)
            'sr_year'           => (int) $now->format('Y'),
            'sr_display'        => 'single',
            'sr_city'           => '',
            'sr_latitude'       => '',
            'sr_longitude'      => '',
            'sr_timezone'       => '',

            // Sinastria (Pessoa 2)
            'p2_name'           => '',
            'p2_birth_date'     => '',
            'p2_birth_time'     => '12:00',
            'p2_unknown_time'   => false,
            'p2_city'           => '',
            'p2_latitude'       => '',
            'p2_longitude'      => '',
            'p2_timezone'       => 'America/Sao_Paulo',
        ];

        $result = null;
        $displayChart = null;
        $biwheelData = null;
        $inputs = $defaults;
        $token = $request->query('r');

        if ($token && preg_match('/^[A-Za-z0-9]{16,64}$/', $token)) {
            $cached = Cache::get(self::CACHE_PREFIX . $token);

            if (is_array($cached)) {
                $inputs = array_merge($defaults, $cached['inputs'] ?? []);

                if (isset($cached['variants']) && is_array($cached['variants'])) {
                    $requestedView = $request->query('sr_view');
                    $activeSrView = in_array($requestedView, ['single', 'biwheel'], true)
                        ? $requestedView
                        : ($cached['default_view'] ?? 'single');

                    $variant = $cached['variants'][$activeSrView] ?? reset($cached['variants']);
                    $result = array_merge($cached, $variant, ['active_sr_view' => $activeSrView]);
                    $inputs['sr_display'] = $activeSrView;
                } else {
                    $result = $cached;
                }

                if (($result['view_type'] ?? null) === 'single' && !empty($result['chart_attributes'])) {
                    $displayChart = new NatalChart($result['chart_attributes']);
                } elseif (($result['view_type'] ?? null) === 'biwheel') {
                    $houseSystem = $result['house_system'] ?? 'placidus';

                    $innerLayer = new ChartLayerData(
                        label: $result['inner_label'] ?? 'Interno',
                        subtitle: $result['inner_subtitle'] ?? '',
                        houseSystem: $houseSystem,
                        planets: $result['inner_planets'] ?? [],
                        angles: $result['inner_angles'] ?? [],
                        houses: $result['inner_houses'] ?? [],
                    );

                    $outerLayer = new ChartLayerData(
                        label: $result['outer_label'] ?? 'Externo',
                        subtitle: $result['outer_subtitle'] ?? '',
                        houseSystem: $houseSystem,
                        planets: $result['outer_planets'] ?? [],
                        angles: $result['outer_angles'] ?? [],
                        houses: [],
                    );

                    $biwheelData = new BiwheelChartData(
                        inner: $innerLayer,
                        outer: $outerLayer,
                        crossAspects: $result['cross_aspects'] ?? [],
                        houseOverlays: $result['house_overlays'] ?? [],
                    );
                }
            } else {
                session()->now('astrology_info', 'O resultado anterior expirou. Clique em calcular novamente para gerar um novo mapa.');
            }
        }

        return view('astrology::public.calculator', [
            'publicLayout'    => $publicLayout,
            'availableThemes' => $availableThemes,
            'inputs'          => $inputs,
            'result'          => $result,
            'displayChart'    => $displayChart,
            'biwheelData'     => $biwheelData,
            'resultToken'     => $result ? $token : null,
        ]);
    }

    public function calculate(
        Request $request,
        ChartCalculationService $calculationService,
        CrossAspectCalculationService $aspectService,
        ChartSvgBuilder $svgBuilder,
        BiwheelChartSvgBuilder $biwheelBuilder
    ) {
        $mode = $request->input('mode', 'natal');
        if (!in_array($mode, ['natal', 'transit', 'solar_return', 'synastry'], true)) {
            $mode = 'natal';
        }

        $rules = [
            'mode'         => 'required|in:natal,transit,solar_return,synastry',
            'name'         => 'nullable|string|max:120',
            'birth_date'   => 'required|date',
            'unknown_time' => 'nullable|boolean',
            'birth_time'   => $request->boolean('unknown_time') ? 'nullable' : 'required|date_format:H:i',
            'city'         => 'nullable|string|max:255',
            'latitude'     => 'required|numeric|between:-90,90',
            'longitude'    => 'required|numeric|between:-180,180',
            'timezone'     => 'nullable|string|max:100',
            'house_system' => 'nullable|in:placidus,koch,equal,whole_sign',
            'theme'        => 'nullable|string|max:80',
        ];

        if ($mode === 'transit') {
            $rules = array_merge($rules, [
                'transit_date'      => 'nullable|date',
                'transit_time'      => 'nullable|date_format:H:i',
                'transit_city'      => 'nullable|string|max:255',
                'transit_latitude'  => 'nullable|numeric|between:-90,90',
                'transit_longitude' => 'nullable|numeric|between:-180,180',
                'transit_timezone'  => 'nullable|string|max:100',
            ]);
        } elseif ($mode === 'solar_return') {
            $rules = array_merge($rules, [
                'sr_year'      => 'required|integer|min:1900|max:2150',
                'sr_display'   => 'nullable|in:biwheel,single',
                'sr_city'      => 'nullable|string|max:255',
                'sr_latitude'  => 'nullable|numeric|between:-90,90',
                'sr_longitude' => 'nullable|numeric|between:-180,180',
                'sr_timezone'  => 'nullable|string|max:100',
            ]);
        } elseif ($mode === 'synastry') {
            $rules = array_merge($rules, [
                'p2_name'         => 'nullable|string|max:120',
                'p2_birth_date'   => 'required|date',
                'p2_unknown_time' => 'nullable|boolean',
                'p2_birth_time'   => $request->boolean('p2_unknown_time') ? 'nullable' : 'required|date_format:H:i',
                'p2_city'         => 'nullable|string|max:255',
                'p2_latitude'     => 'required|numeric|between:-90,90',
                'p2_longitude'    => 'required|numeric|between:-180,180',
                'p2_timezone'     => 'nullable|string|max:100',
            ]);
        }

        $messages = [
            'birth_date.required'    => 'Informe a data de nascimento.',
            'birth_time.required'    => 'Informe a hora de nascimento ou marque que não sabe a hora exata.',
            'latitude.required'      => 'Selecione a cidade na lista de sugestões ou preencha latitude e longitude em Opções avançadas.',
            'longitude.required'     => 'Selecione a cidade na lista de sugestões ou preencha latitude e longitude em Opções avançadas.',
            'p2_birth_date.required' => 'Informe a data de nascimento da 2ª pessoa.',
            'p2_birth_time.required' => 'Informe a hora da 2ª pessoa ou marque que não sabe a hora exata.',
            'p2_latitude.required'   => 'Selecione a cidade da 2ª pessoa na lista de sugestões ou preencha latitude e longitude manualmente.',
            'p2_longitude.required'  => 'Selecione a cidade da 2ª pessoa na lista de sugestões ou preencha latitude e longitude manualmente.',
            'sr_year.required'       => 'Informe o ano desejado para a Revolução Solar.',
        ];

        $validated = $request->validate($rules, $messages);

        $themeSlug = (string) ($validated['theme'] ?? 'classic');
        $availableThemes = ThemeManager::getThemes();
        $defaultTheme = $this->resolvePublicTheme($availableThemes);
        if (!isset($availableThemes[$themeSlug])) {
            $themeSlug = $defaultTheme;
        }

        $houseSystem = (string) ($validated['house_system'] ?? 'placidus');
        $p1UnknownTime = $request->boolean('unknown_time');
        $p1Time = $p1UnknownTime ? '12:00' : (string) ($validated['birth_time'] ?? '12:00');
        $p1Timezone = !empty($validated['timezone']) ? (string) $validated['timezone'] : 'America/Sao_Paulo';
        $p1Name = trim((string) ($validated['name'] ?? '')) ?: 'Mapa Natal';
        $p1Location = trim((string) ($validated['city'] ?? '')) ?: "{$validated['latitude']}, {$validated['longitude']}";

        $inputs = $request->only([
            'mode', 'name', 'birth_date', 'birth_time', 'city', 'latitude', 'longitude', 'timezone',
            'house_system', 'theme',
            'transit_date', 'transit_time', 'transit_city', 'transit_latitude', 'transit_longitude', 'transit_timezone',
            'sr_year', 'sr_display', 'sr_city', 'sr_latitude', 'sr_longitude', 'sr_timezone',
            'p2_name', 'p2_birth_date', 'p2_birth_time', 'p2_city', 'p2_latitude', 'p2_longitude', 'p2_timezone',
        ]);
        $inputs['unknown_time'] = $p1UnknownTime;
        $inputs['p2_unknown_time'] = $request->boolean('p2_unknown_time');
        $inputs['birth_time'] = $p1Time;
        $inputs['timezone'] = $p1Timezone;
        $inputs['house_system'] = $houseSystem;
        $inputs['theme'] = $themeSlug;
        $inputs['sr_display'] = ($validated['sr_display'] ?? 'single') === 'biwheel' ? 'biwheel' : 'single';

        try {
            if ($mode === 'natal') {
                $payload = $this->buildNatalPayload(
                    $p1Name,
                    $validated['birth_date'],
                    $p1Time,
                    $p1UnknownTime,
                    $p1Timezone,
                    $p1Location,
                    (float) $validated['latitude'],
                    (float) $validated['longitude'],
                    $houseSystem,
                    $themeSlug,
                    $calculationService,
                    $svgBuilder
                );
            } elseif ($mode === 'transit') {
                $payload = $this->buildTransitPayload(
                    $p1Name,
                    $validated,
                    $p1Time,
                    $p1UnknownTime,
                    $p1Timezone,
                    $p1Location,
                    $houseSystem,
                    $themeSlug,
                    $calculationService,
                    $aspectService,
                    $biwheelBuilder
                );
            } elseif ($mode === 'solar_return') {
                $payload = $this->buildSolarReturnPayload(
                    $p1Name,
                    $validated,
                    $p1Time,
                    $p1UnknownTime,
                    $p1Timezone,
                    $p1Location,
                    $houseSystem,
                    $themeSlug,
                    $calculationService,
                    $aspectService,
                    $svgBuilder,
                    $biwheelBuilder
                );
            } else {
                $payload = $this->buildSynastryPayload(
                    $validated,
                    $p1Time,
                    $p1UnknownTime,
                    $p1Timezone,
                    $p1Location,
                    $request->boolean('p2_unknown_time'),
                    $houseSystem,
                    $themeSlug,
                    $calculationService,
                    $aspectService,
                    $biwheelBuilder
                );
            }
        } catch (Throwable $e) {
            return back()
                ->withInput()
                ->with('astrology_error', 'Não foi possível calcular o mapa neste momento: ' . $e->getMessage());
        }

        $payload['mode'] = $mode;
        $payload['include_outer_angles'] = $mode !== 'transit';
        $payload['theme'] = $themeSlug;
        $payload['inputs'] = $inputs;

        $token = Str::random(32);
        Cache::put(self::CACHE_PREFIX . $token, $payload, now()->addMinutes(90));

        return redirect()->route('astrology.calculator.index', ['r' => $token]);
    }

    public function downloadSvg(Request $request, string $token)
    {
        if (!preg_match('/^[A-Za-z0-9]{16,64}$/', $token)) {
            abort(404);
        }

        $cached = Cache::get(self::CACHE_PREFIX . $token);
        if (!is_array($cached)) {
            return redirect()
                ->route('astrology.calculator.index')
                ->with('astrology_info', 'O arquivo SVG expirou. Calcule o mapa novamente para baixar.');
        }

        if (isset($cached['variants']) && is_array($cached['variants'])) {
            $requestedView = $request->query('sr_view');
            $activeView = in_array($requestedView, ['single', 'biwheel'], true)
                ? $requestedView
                : ($cached['default_view'] ?? 'single');
            $source = $cached['variants'][$activeView] ?? reset($cached['variants']);
        } else {
            $source = $cached;
        }

        if (empty($source['svg'])) {
            return redirect()
                ->route('astrology.calculator.index')
                ->with('astrology_info', 'O arquivo SVG expirou. Calcule o mapa novamente para baixar.');
        }

        $filename = $source['filename'] ?? 'mapa-astral.svg';

        return response($source['svg'], 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    protected function buildNatalPayload(
        string $name,
        string $birthDate,
        string $birthTime,
        bool $unknownTime,
        string $timezone,
        string $location,
        float $lat,
        float $lon,
        string $houseSystem,
        string $themeSlug,
        ChartCalculationService $calculationService,
        ChartSvgBuilder $svgBuilder
    ): array {
        $chartData = $calculationService->calculate([
            'birth_date'     => $birthDate,
            'birth_time'     => $birthTime,
            'timezone'       => $timezone,
            'latitude'       => $lat,
            'longitude'      => $lon,
            'house_system'   => $houseSystem,
            'include_minors' => false,
            'orb_general'    => 6.0,
            'orb_luminaries' => 10.0,
        ]);

        $summary = $calculationService->extractSummarySigns($chartData);
        $attributes = [
            'name'           => $name,
            'slug'           => Str::slug($name) ?: 'mapa-natal',
            'birth_date'     => $birthDate,
            'birth_time'     => $birthTime,
            'timezone'       => $timezone,
            'city'           => $location,
            'latitude'       => $lat,
            'longitude'      => $lon,
            'house_system'   => $houseSystem,
            'include_minors' => false,
            'orb_general'    => 6.0,
            'orb_luminaries' => 10.0,
            'status'         => 'completed',
            'sun_sign'       => $summary['sun_sign'],
            'moon_sign'      => $summary['moon_sign'],
            'ascendant_sign' => $summary['ascendant_sign'],
            'chart_data'     => $chartData,
        ];

        $chart = new NatalChart($attributes);
        $svg = $svgBuilder->build($chart, $themeSlug);
        $slug = Str::slug($name) ?: 'mapa-natal';

        return [
            'view_type'        => 'single',
            'title'            => $name,
            'subtitle'         => $chart->formatted_date_time . " ({$timezone}) — {$location}",
            'unknown_time'     => $unknownTime,
            'svg'              => $svg,
            'filename'         => "{$slug}-natal.svg",
            'chart_attributes' => $attributes,
        ];
    }

    protected function buildTransitPayload(
        string $p1Name,
        array $validated,
        string $p1Time,
        bool $p1UnknownTime,
        string $p1Timezone,
        string $p1Location,
        string $houseSystem,
        string $themeSlug,
        ChartCalculationService $calculationService,
        CrossAspectCalculationService $aspectService,
        BiwheelChartSvgBuilder $biwheelBuilder
    ): array {
        $natalData = $calculationService->calculateEphemeral([
            'birth_date'   => $validated['birth_date'],
            'birth_time'   => $p1Time,
            'timezone'     => $p1Timezone,
            'latitude'     => (float) $validated['latitude'],
            'longitude'    => (float) $validated['longitude'],
            'house_system' => $houseSystem,
            'aspects'      => false,
        ]);

        $transitTz = !empty($validated['transit_timezone']) ? (string) $validated['transit_timezone'] : $p1Timezone;
        $nowInTz = Carbon::now($transitTz);
        $transitDate = !empty($validated['transit_date']) ? (string) $validated['transit_date'] : $nowInTz->format('Y-m-d');
        $transitTime = !empty($validated['transit_time']) ? (string) $validated['transit_time'] : $nowInTz->format('H:i');
        $transitLat = isset($validated['transit_latitude']) && $validated['transit_latitude'] !== ''
            ? (float) $validated['transit_latitude']
            : (float) $validated['latitude'];
        $transitLon = isset($validated['transit_longitude']) && $validated['transit_longitude'] !== ''
            ? (float) $validated['transit_longitude']
            : (float) $validated['longitude'];
        $transitLoc = trim((string) ($validated['transit_city'] ?? '')) ?: $p1Location;

        $transitData = $calculationService->calculateEphemeral([
            'date'         => $transitDate,
            'time'         => $transitTime,
            'timezone'     => $transitTz,
            'latitude'     => $transitLat,
            'longitude'    => $transitLon,
            'house_system' => $houseSystem,
            'aspects'      => false,
        ]);

        $natalFormattedDt = Carbon::parse($validated['birth_date'])->format('d/m/Y') . ' às ' . substr($p1Time, 0, 5);
        $transitFormattedDt = Carbon::parse($transitDate)->format('d/m/Y') . ' às ' . substr($transitTime, 0, 5);

        $innerLayer = ChartLayerData::fromCalculatedData(
            chartData: $natalData,
            label: $p1Name,
            subtitle: "{$natalFormattedDt} ({$p1Timezone}) — {$p1Location}",
            houseSystem: $houseSystem,
            timezone: $p1Timezone,
        );

        $outerLayer = ChartLayerData::fromCalculatedData(
            chartData: $transitData,
            label: 'Trânsitos',
            subtitle: "{$transitFormattedDt} ({$transitTz}) — {$transitLoc}",
            houseSystem: $houseSystem,
            timezone: $transitTz,
        );

        $crossAspects = $aspectService->calculate($innerLayer, $outerLayer, [
            'orb_general'          => 2.5,
            'orb_luminaries'       => 4.0,
            'include_minors'       => false,
            'include_outer_angles' => false,
            'applying_mode'        => 'outer_only',
        ]);

        $houseOverlays = $aspectService->calculateHouseOverlays($innerLayer, $outerLayer);
        $biwheelData = new BiwheelChartData(
            inner: $innerLayer,
            outer: $outerLayer,
            crossAspects: $crossAspects,
            houseOverlays: $houseOverlays,
        );

        $svg = $biwheelBuilder->build($biwheelData, $themeSlug);

        return [
            'view_type'      => 'biwheel',
            'title'          => "Trânsitos para {$p1Name}",
            'subtitle'       => "Céu de {$transitFormattedDt} sobre o mapa natal",
            'unknown_time'   => $p1UnknownTime,
            'svg'            => $svg,
            'filename'       => "transitos-{$transitDate}.svg",
            'house_system'   => $houseSystem,
            'inner_label'    => $innerLayer->label,
            'inner_subtitle' => $innerLayer->subtitle,
            'inner_summary'  => $calculationService->extractSummarySigns($natalData),
            'inner_planets'  => $innerLayer->planets,
            'inner_angles'   => $innerLayer->angles,
            'inner_houses'   => $innerLayer->houses,
            'outer_label'    => $outerLayer->label,
            'outer_subtitle' => $outerLayer->subtitle,
            'outer_summary'  => $calculationService->extractSummarySigns($transitData),
            'outer_planets'  => $outerLayer->planets,
            'outer_angles'   => $outerLayer->angles,
            'cross_aspects'  => $crossAspects,
            'house_overlays' => $houseOverlays,
        ];
    }

    protected function buildSolarReturnPayload(
        string $p1Name,
        array $validated,
        string $p1Time,
        bool $p1UnknownTime,
        string $p1Timezone,
        string $p1Location,
        string $houseSystem,
        string $themeSlug,
        ChartCalculationService $calculationService,
        CrossAspectCalculationService $aspectService,
        ChartSvgBuilder $svgBuilder,
        BiwheelChartSvgBuilder $biwheelBuilder
    ): array {
        $natalData = $calculationService->calculateEphemeral([
            'birth_date'   => $validated['birth_date'],
            'birth_time'   => $p1Time,
            'timezone'     => $p1Timezone,
            'latitude'     => (float) $validated['latitude'],
            'longitude'    => (float) $validated['longitude'],
            'house_system' => $houseSystem,
            'aspects'      => false,
        ]);

        $natalSummary = $calculationService->extractSummarySigns($natalData);
        $natalChart = new NatalChart([
            'name'           => $p1Name,
            'slug'           => Str::slug($p1Name) ?: 'mapa-natal',
            'birth_date'     => $validated['birth_date'],
            'birth_time'     => $p1Time,
            'timezone'       => $p1Timezone,
            'city'           => $p1Location,
            'latitude'       => (float) $validated['latitude'],
            'longitude'      => (float) $validated['longitude'],
            'house_system'   => $houseSystem,
            'include_minors' => false,
            'orb_general'    => 6.0,
            'orb_luminaries' => 10.0,
            'status'         => 'completed',
            'sun_sign'       => $natalSummary['sun_sign'],
            'moon_sign'      => $natalSummary['moon_sign'],
            'ascendant_sign' => $natalSummary['ascendant_sign'],
            'chart_data'     => $natalData,
        ]);

        $srYear = (int) $validated['sr_year'];
        $srDisplay = ($validated['sr_display'] ?? 'single') === 'biwheel' ? 'biwheel' : 'single';
        $srTz = !empty($validated['sr_timezone']) ? (string) $validated['sr_timezone'] : $p1Timezone;
        $srLat = isset($validated['sr_latitude']) && $validated['sr_latitude'] !== ''
            ? (float) $validated['sr_latitude']
            : (float) $validated['latitude'];
        $srLon = isset($validated['sr_longitude']) && $validated['sr_longitude'] !== ''
            ? (float) $validated['sr_longitude']
            : (float) $validated['longitude'];
        $srLoc = trim((string) ($validated['sr_city'] ?? '')) ?: $p1Location;

        $srData = $calculationService->calculateSolarReturn($natalChart, $srYear, [
            'latitude'       => $srLat,
            'longitude'      => $srLon,
            'timezone'       => $srTz,
            'house_system'   => $houseSystem,
            'include_minors' => false,
            'orb_general'    => 6.0,
            'orb_luminaries' => 10.0,
        ]);

        $solarReturnMeta = $srData['solar_return'] ?? [];
        $localDt = !empty($solarReturnMeta['local_datetime'])
            ? Carbon::parse($solarReturnMeta['local_datetime'])
            : Carbon::now($srTz);

        $srSummary = $calculationService->extractSummarySigns($srData);

        // Variante 1: Mapa simples isolado da Revolução Solar
        $singleAttributes = [
            'name'           => "{$p1Name} — Revolução Solar {$srYear}",
            'slug'           => 'revolucao-solar-' . $srYear,
            'birth_date'     => $localDt->format('Y-m-d'),
            'birth_time'     => $localDt->format('H:i:s'),
            'timezone'       => $srTz,
            'city'           => $srLoc,
            'latitude'       => $srLat,
            'longitude'      => $srLon,
            'house_system'   => $houseSystem,
            'include_minors' => false,
            'orb_general'    => 6.0,
            'orb_luminaries' => 10.0,
            'status'         => 'completed',
            'sun_sign'       => $srSummary['sun_sign'],
            'moon_sign'      => $srSummary['moon_sign'],
            'ascendant_sign' => $srSummary['ascendant_sign'],
            'chart_data'     => $srData,
        ];

        $srChart = new NatalChart($singleAttributes);
        $singleVariant = [
            'view_type'         => 'single',
            'title'             => "Revolução Solar {$srYear} — {$p1Name}",
            'subtitle'          => $localDt->format('d/m/Y \à\s H:i:s') . " ({$srTz}) — {$srLoc}",
            'unknown_time'      => $p1UnknownTime,
            'solar_return_meta' => $solarReturnMeta,
            'svg'               => $svgBuilder->build($srChart, $themeSlug),
            'filename'          => "revolucao-solar-{$srYear}.svg",
            'chart_attributes'  => $singleAttributes,
        ];

        // Variante 2: Revolução Solar sobreposta ao Mapa Natal
        $natalFormattedDt = Carbon::parse($validated['birth_date'])->format('d/m/Y') . ' às ' . substr($p1Time, 0, 5);

        $innerLayer = ChartLayerData::fromCalculatedData(
            chartData: $srData,
            label: "Revolução Solar {$srYear}",
            subtitle: $localDt->format('d/m/Y \à\s H:i:s') . " ({$srTz}) — {$srLoc}",
            houseSystem: $houseSystem,
            timezone: $srTz,
        );

        $outerLayer = ChartLayerData::fromCalculatedData(
            chartData: $natalData,
            label: "{$p1Name} (Natal)",
            subtitle: "{$natalFormattedDt} ({$p1Timezone}) — {$p1Location}",
            houseSystem: $houseSystem,
            timezone: $p1Timezone,
        );

        $crossAspects = $aspectService->calculate($innerLayer, $outerLayer, [
            'orb_general'          => 5.0,
            'orb_luminaries'       => 8.0,
            'include_minors'       => false,
            'include_outer_angles' => true,
            'applying_mode'        => 'none',
        ]);

        $houseOverlays = $aspectService->calculateHouseOverlays($innerLayer, $outerLayer);
        $reverseHouseOverlays = $aspectService->calculateHouseOverlays($outerLayer, $innerLayer);

        $biwheelData = new BiwheelChartData(
            inner: $innerLayer,
            outer: $outerLayer,
            crossAspects: $crossAspects,
            houseOverlays: $houseOverlays,
        );

        $biwheelVariant = [
            'view_type'              => 'biwheel',
            'title'                  => "Revolução Solar {$srYear} + Mapa Natal",
            'subtitle'               => "Retorno exato em " . $localDt->format('d/m/Y \à\s H:i:s') . " ({$srLoc})",
            'unknown_time'           => $p1UnknownTime,
            'solar_return_meta'      => $solarReturnMeta,
            'svg'                    => $biwheelBuilder->build($biwheelData, $themeSlug),
            'filename'               => "revolucao-solar-{$srYear}-biwheel.svg",
            'house_system'           => $houseSystem,
            'inner_label'            => $innerLayer->label,
            'inner_subtitle'         => $innerLayer->subtitle,
            'inner_summary'          => $srSummary,
            'inner_planets'          => $innerLayer->planets,
            'inner_angles'           => $innerLayer->angles,
            'inner_houses'           => $innerLayer->houses,
            'outer_label'            => $outerLayer->label,
            'outer_subtitle'         => $outerLayer->subtitle,
            'outer_summary'          => $natalSummary,
            'outer_planets'          => $outerLayer->planets,
            'outer_angles'           => $outerLayer->angles,
            'cross_aspects'          => $crossAspects,
            'house_overlays'         => $houseOverlays,
            'reverse_house_overlays' => $reverseHouseOverlays,
        ];

        return [
            'default_view' => $srDisplay,
            'variants'     => [
                'single'  => $singleVariant,
                'biwheel' => $biwheelVariant,
            ],
        ];
    }

    protected function buildSynastryPayload(
        array $validated,
        string $p1Time,
        bool $p1UnknownTime,
        string $p1Timezone,
        string $p1Location,
        bool $p2UnknownTime,
        string $houseSystem,
        string $themeSlug,
        ChartCalculationService $calculationService,
        CrossAspectCalculationService $aspectService,
        BiwheelChartSvgBuilder $biwheelBuilder
    ): array {
        $p1DisplayName = trim((string) ($validated['name'] ?? '')) ?: 'Pessoa 1';
        $p2DisplayName = trim((string) ($validated['p2_name'] ?? '')) ?: 'Pessoa 2';
        $p2Time = $p2UnknownTime ? '12:00' : (string) ($validated['p2_birth_time'] ?? '12:00');
        $p2Timezone = !empty($validated['p2_timezone']) ? (string) $validated['p2_timezone'] : 'America/Sao_Paulo';
        $p2Location = trim((string) ($validated['p2_city'] ?? '')) ?: "{$validated['p2_latitude']}, {$validated['p2_longitude']}";

        $p1Data = $calculationService->calculateEphemeral([
            'birth_date'   => $validated['birth_date'],
            'birth_time'   => $p1Time,
            'timezone'     => $p1Timezone,
            'latitude'     => (float) $validated['latitude'],
            'longitude'    => (float) $validated['longitude'],
            'house_system' => $houseSystem,
            'aspects'      => false,
        ]);

        $p2Data = $calculationService->calculateEphemeral([
            'birth_date'   => $validated['p2_birth_date'],
            'birth_time'   => $p2Time,
            'timezone'     => $p2Timezone,
            'latitude'     => (float) $validated['p2_latitude'],
            'longitude'    => (float) $validated['p2_longitude'],
            'house_system' => $houseSystem,
            'aspects'      => false,
        ]);

        $p1FormattedDt = Carbon::parse($validated['birth_date'])->format('d/m/Y') . ' às ' . substr($p1Time, 0, 5);
        $p2FormattedDt = Carbon::parse($validated['p2_birth_date'])->format('d/m/Y') . ' às ' . substr($p2Time, 0, 5);

        $innerLayer = ChartLayerData::fromCalculatedData(
            chartData: $p1Data,
            label: $p1DisplayName,
            subtitle: "{$p1FormattedDt} ({$p1Timezone}) — {$p1Location}",
            houseSystem: $houseSystem,
            timezone: $p1Timezone,
        );

        $outerLayer = ChartLayerData::fromCalculatedData(
            chartData: $p2Data,
            label: $p2DisplayName,
            subtitle: "{$p2FormattedDt} ({$p2Timezone}) — {$p2Location}",
            houseSystem: $houseSystem,
            timezone: $p2Timezone,
        );

        $crossAspects = $aspectService->calculate($innerLayer, $outerLayer, [
            'orb_general'          => 5.0,
            'orb_luminaries'       => 8.0,
            'include_minors'       => false,
            'include_outer_angles' => true,
            'applying_mode'        => 'none',
        ]);

        $houseOverlays = $aspectService->calculateHouseOverlays($innerLayer, $outerLayer);
        $reverseHouseOverlays = $aspectService->calculateHouseOverlays($outerLayer, $innerLayer);

        $biwheelData = new BiwheelChartData(
            inner: $innerLayer,
            outer: $outerLayer,
            crossAspects: $crossAspects,
            houseOverlays: $houseOverlays,
        );

        $svg = $biwheelBuilder->build($biwheelData, $themeSlug);

        return [
            'view_type'              => 'biwheel',
            'title'                  => "Sinastria: {$p1DisplayName} & {$p2DisplayName}",
            'subtitle'               => 'Comparação entre os dois mapas natais',
            'unknown_time'           => $p1UnknownTime || $p2UnknownTime,
            'svg'                    => $svg,
            'filename'               => 'sinastria-' . Str::slug("{$p1DisplayName}-{$p2DisplayName}") . '.svg',
            'house_system'           => $houseSystem,
            'inner_label'            => $innerLayer->label,
            'inner_subtitle'         => $innerLayer->subtitle,
            'inner_summary'          => $calculationService->extractSummarySigns($p1Data),
            'inner_planets'          => $innerLayer->planets,
            'inner_angles'           => $innerLayer->angles,
            'inner_houses'           => $innerLayer->houses,
            'outer_label'            => $outerLayer->label,
            'outer_subtitle'         => $outerLayer->subtitle,
            'outer_summary'          => $calculationService->extractSummarySigns($p2Data),
            'outer_planets'          => $outerLayer->planets,
            'outer_angles'           => $outerLayer->angles,
            'cross_aspects'          => $crossAspects,
            'house_overlays'         => $houseOverlays,
            'reverse_house_overlays' => $reverseHouseOverlays,
        ];
    }

    protected function resolvePublicLayout(): ?string
    {
        $configured = config('pluginSettings.Astrology.publicLayout');

        if (!empty($configured) && view()->exists($configured)) {
            return $configured;
        }

        if (view()->exists('layouts.app')) {
            return 'layouts.app';
        }

        return null;
    }

    protected function resolvePublicTheme(array $availableThemes): string
    {
        $configured = trim((string) config('pluginSettings.Astrology.publicTheme', 'classic'));

        if ($configured !== '' && isset($availableThemes[$configured])) {
            return $configured;
        }

        if (isset($availableThemes['classic'])) {
            return 'classic';
        }

        return (string) (array_key_first($availableThemes) ?? 'classic');
    }
}
