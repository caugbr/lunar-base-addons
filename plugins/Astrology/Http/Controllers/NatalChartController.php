<?php

namespace Plugins\Astrology\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Plugins\Astrology\Jobs\CalculateNatalChartJob;
use Plugins\Astrology\Models\NatalChart;
use Carbon\Carbon;
use Plugins\Astrology\Services\ChartCalculationService;
use Plugins\Astrology\Services\ChartSvgBuilder;

class NatalChartController extends Controller
{
    public function index(Request $request)
    {
        $query = NatalChart::with('creator');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('sun_sign')) {
            $query->where('sun_sign', $request->sun_sign);
        }

        if ($request->filled('ascendant_sign')) {
            $query->where('ascendant_sign', $request->ascendant_sign);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_public')) {
            $query->where('is_public', $request->boolean('is_public'));
        }

        $perPage = function_exists('setting') ? setting('reading.pagination_max_items', 15) : 15;
        $charts = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return view('astrology::admin.charts.index', compact('charts'));
    }

    public function create()
    {
        return view('astrology::admin.charts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'slug'           => 'nullable|string|max:255|unique:natal_charts,slug|alpha_dash',
            'birth_date'     => 'required|date',
            'birth_time'     => 'required|date_format:H:i',
            'timezone'       => 'required|string|max:100',
            'city'           => 'nullable|string|max:255',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'house_system'   => 'required|string|in:placidus,koch,equal,whole_sign',
            'include_minors' => 'boolean',
            'orb_general'    => 'required|numeric|min:0.5|max:15',
            'orb_luminaries' => 'required|numeric|min:0.5|max:15',
            'is_public'      => 'boolean',
        ]);

        $validated['include_minors'] = $request->boolean('include_minors');
        $validated['is_public'] = $request->boolean('is_public');
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'pending';

        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (NatalChart::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        $chart = NatalChart::create($validated);

        try {
            CalculateNatalChartJob::dispatchSync($chart);
            $chart->refresh();

            return redirect()->route('admin.astrology.charts.show', $chart->id)
                ->with('success', "Mapa astral de '{$chart->name}' calculado com sucesso.");
        } catch (\Throwable $e) {
            return redirect()->route('admin.astrology.charts.show', $chart->id)
                ->with('error', 'O mapa foi salvo, mas o cálculo falhou: ' . $e->getMessage());
        }
    }

    public function update(Request $request, NatalChart $chart)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'slug'           => "required|string|max:255|alpha_dash|unique:natal_charts,slug,{$chart->id}",
            'birth_date'     => 'required|date',
            'birth_time'     => 'required',
            'timezone'       => 'required|string|max:100',
            'city'           => 'nullable|string|max:255',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'house_system'   => 'required|string|in:placidus,koch,equal,whole_sign',
            'include_minors' => 'boolean',
            'orb_general'    => 'required|numeric|min:0.5|max:15',
            'orb_luminaries' => 'required|numeric|min:0.5|max:15',
            'is_public'      => 'boolean',
        ]);

        $validated['include_minors'] = $request->boolean('include_minors');
        $validated['is_public'] = $request->boolean('is_public');
        $validated['birth_time'] = substr($validated['birth_time'], 0, 5);
        $validated['status'] = 'pending';
        $validated['error_message'] = null;

        $chart->update($validated);
        $chart->forgetRenderedSvgCache();

        try {
            CalculateNatalChartJob::dispatchSync($chart);
            $chart->refresh();

            return redirect()->route('admin.astrology.charts.show', $chart->id)
                ->with('success', 'Dados atualizados e mapa recalculado com sucesso.');
        } catch (\Throwable $e) {
            return redirect()->route('admin.astrology.charts.show', $chart->id)
                ->with('error', 'Dados atualizados, mas o recálculo falhou: ' . $e->getMessage());
        }
    }

    public function recalculate(NatalChart $chart)
    {
        $chart->update([
            'status'        => 'pending',
            'error_message' => null,
        ]);
        $chart->forgetRenderedSvgCache();

        try {
            CalculateNatalChartJob::dispatchSync($chart);
            $chart->refresh();

            return back()->with('success', 'Mapa recalculado com sucesso a partir das efemérides.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Erro ao recalcular mapa: ' . $e->getMessage());
        }
    }

    public function show(Request $request, NatalChart $chart)
    {
        $chart->load('creator');
        $themeSlug = $request->query('theme', 'classic');
        $compiledSvg = $chart->getRenderedSvg($themeSlug);

        return view('astrology::admin.charts.show', compact('chart', 'compiledSvg', 'themeSlug'));
    }

    public function edit(NatalChart $chart)
    {
        return view('astrology::admin.charts.edit', compact('chart'));
    }

    public function downloadSvg(Request $request, NatalChart $chart)
    {
        $themeSlug = $request->query('theme', 'classic');
        $svgContent = $chart->getRenderedSvg($themeSlug);
        $filename = "mapa-{$chart->slug}-{$themeSlug}.svg";

        return response($svgContent, 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function destroy(NatalChart $chart)
    {
        $chart->forgetRenderedSvgCache();
        $chart->delete();

        return redirect()->route('admin.astrology.charts.index')
            ->with('success', 'Mapa astral removido com sucesso.');
    }

    public function apiData(NatalChart $chart)
    {
        if (!$chart->is_public && !auth()->check()) {
            abort(403, 'Acesso restrito.');
        }

        return response()->json([
            'id'            => $chart->id,
            'name'          => $chart->name,
            'slug'          => $chart->slug,
            'status'        => $chart->status,
            'error_message' => $chart->error_message,
            'is_completed'  => $chart->isCompleted(),
            'birth_date'    => $chart->birth_date->format('Y-m-d'),
            'birth_time'    => $chart->birth_time,
            'city'          => $chart->city,
            'signs'         => [
                'sun'       => $chart->sun_sign,
                'moon'      => $chart->moon_sign,
                'ascendant' => $chart->ascendant_sign,
            ],
            'chart'         => $chart->chart_data,
        ]);
    }

    public function publicShow(string $slug)
    {
        $chart = NatalChart::where('slug', $slug)
            ->where('is_public', true)
            ->firstOrFail();

        return view('astrology::public.show', compact('chart'));
    }

    public function solarReturn(
        Request $request,
        NatalChart $chart,
        ChartCalculationService $calculationService,
        ChartSvgBuilder $svgBuilder
    ) {
        if (!$chart->isCompleted()) {
            return redirect()->route('admin.astrology.charts.show', $chart->id)
                ->with('error', 'Calcule o mapa natal antes de levantar a Revolução Solar.');
        }

        $srState = $this->resolveSolarReturnState($request, $chart);
        $themeSlug = (string) $request->query('theme', 'classic');

        try {
            [$displayChart, $solarReturnMeta] = $this->buildEphemeralSolarReturnChart($chart, $srState, $calculationService);
            $compiledSvg = $svgBuilder->build($displayChart, $themeSlug);
            $calculationError = null;
        } catch (\Throwable $e) {
            $displayChart = $chart;
            $solarReturnMeta = null;
            $compiledSvg = '';
            $calculationError = $e->getMessage();
        }

        return view('astrology::admin.charts.show', array_merge($srState, [
            'chart'            => $chart,
            'displayChart'     => $displayChart,
            'compiledSvg'      => $compiledSvg,
            'themeSlug'        => $themeSlug,
            'isSolarReturn'    => true,
            'solarReturnMeta'  => $solarReturnMeta,
            'calculationError' => $calculationError,
        ]));
    }

    public function solarReturnDownloadSvg(
        Request $request,
        NatalChart $chart,
        ChartCalculationService $calculationService,
        ChartSvgBuilder $svgBuilder
    ) {
        abort_unless($chart->isCompleted(), 404, 'Mapa natal não calculado.');

        $srState = $this->resolveSolarReturnState($request, $chart);
        $themeSlug = (string) $request->query('theme', 'classic');

        [$displayChart] = $this->buildEphemeralSolarReturnChart($chart, $srState, $calculationService);
        $svgContent = $svgBuilder->build($displayChart, $themeSlug);
        $filename = "revolucao-solar-{$chart->slug}-{$srState['srYear']}-{$themeSlug}.svg";

        return response($svgContent, 200, [
            'Content-Type'        => 'image/svg+xml',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    protected function resolveSolarReturnState(Request $request, NatalChart $chart): array
    {
        $tz = (string) $request->query('sr_timezone', $chart->timezone ?? 'America/Sao_Paulo');
        $defaultYear = (int) Carbon::now($tz)->format('Y');

        return [
            'srYear'        => (int) $request->query('sr_year', $defaultYear),
            'srHouseSystem' => (string) $request->query('sr_house_system', $chart->house_system ?? 'placidus'),
            'srTimezone'    => $tz,
            'srCity'        => (string) $request->query('sr_city', $chart->city ?? ''),
            'srLatitude'    => (float) $request->query('sr_latitude', $chart->latitude),
            'srLongitude'   => (float) $request->query('sr_longitude', $chart->longitude),
        ];
    }

    protected function buildEphemeralSolarReturnChart(
        NatalChart $chart,
        array $srState,
        ChartCalculationService $calculationService
    ): array {
        $srData = $calculationService->calculateSolarReturn($chart, $srState['srYear'], [
            'latitude'       => $srState['srLatitude'],
            'longitude'      => $srState['srLongitude'],
            'timezone'       => $srState['srTimezone'],
            'house_system'   => $srState['srHouseSystem'],
            'include_minors' => $chart->include_minors,
            'orb_general'    => $chart->orb_general,
            'orb_luminaries' => $chart->orb_luminaries,
        ]);

        $solarReturnMeta = $srData['solar_return'] ?? [];
        $localDt = !empty($solarReturnMeta['local_datetime'])
            ? Carbon::parse($solarReturnMeta['local_datetime'])
            : Carbon::now($srState['srTimezone']);

        $summary = $calculationService->extractSummarySigns($srData);
        $loc = $srState['srCity'] ?: "{$srState['srLatitude']}, {$srState['srLongitude']}";

        $displayChart = new NatalChart([
            'name'           => "{$chart->name} — Revolução Solar {$srState['srYear']}",
            'slug'           => "{$chart->slug}-rs-{$srState['srYear']}",
            'birth_date'     => $localDt->format('Y-m-d'),
            'birth_time'     => $localDt->format('H:i:s'),
            'timezone'       => $srState['srTimezone'],
            'city'           => $loc,
            'latitude'       => $srState['srLatitude'],
            'longitude'      => $srState['srLongitude'],
            'house_system'   => $srState['srHouseSystem'],
            'include_minors' => $chart->include_minors,
            'orb_general'    => $chart->orb_general,
            'orb_luminaries' => $chart->orb_luminaries,
            'status'         => 'completed',
            'sun_sign'       => $summary['sun_sign'],
            'moon_sign'      => $summary['moon_sign'],
            'ascendant_sign' => $summary['ascendant_sign'],
            'chart_data'     => $srData,
        ]);

        return [$displayChart, $solarReturnMeta];
    }
}
