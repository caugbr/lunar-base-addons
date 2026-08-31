<?php

namespace Plugins\Tracker\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Tracker\Models\Tracker;
use Plugins\Tracker\Models\TrackerEvent;

class TrackerController extends Controller
{
    /**
     * Endpoint público para registrar eventos de clique via sendBeacon/fetch
     */
    public function recordEvent(Request $request)
    {
        $eventName = trim($request->input('event_name'));

        if (empty($eventName)) {
            return response()->json(['error' => 'Nome do evento inválido'], 422);
        }

        // Gera o mesmo hash anônimo LGPD usado nas pageviews
        $visitorHash = hash('sha256', $request->ip() . today()->toDateString() . config('app.key'));

        try {
            TrackerEvent::create([
                'event_name' => substr($eventName, 0, 100),
                'event_category' => $request->input('event_category') ? substr($request->input('event_category'), 0, 50) : null,
                'path' => '/' . ltrim($request->input('path', $request->path()), '/'),
                'visitor_hash' => $visitorHash,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Silencia falhas para nunca travar o front-end
        }

        return response()->json(['success' => true]);
    }

    /**
     * Dashboard Principal com Resumo dos Boxes
     */
    public function index()
    {
        $days = (int) setting('pluginSettings.Tracker.dashboardDays', 30);
        $topPagesLimit = (int) setting('pluginSettings.Tracker.topPagesLimit', 10);
        $topReferrersLimit = (int) setting('pluginSettings.Tracker.topReferrersLimit', 5);
        $topEventsLimit = (int) setting('pluginSettings.Tracker.topEventsLimit', 5);

        $startDate = now()->subDays($days)->startOfDay();

        // 1. Total de visualizações e visitantes únicos
        $totalViews = Tracker::where('created_at', '>=', $startDate)->count();
        $uniqueVisitors = Tracker::where('created_at', '>=', $startDate)->distinct('visitor_hash')->count('visitor_hash');

        // 2. Gráfico diário de visualizações
        $dailyViews = Tracker::where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as views'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('views', 'date');

        // 3. Gráfico de Visitações por Horário (00h às 23h)
        $rawHourly = Tracker::where('created_at', '>=', $startDate)
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as total'))
            ->groupBy('hour')
            ->pluck('total', 'hour')
            ->toArray();

        $hourlyViews = [];
        for ($i = 0; $i < 24; $i++) {
            $label = sprintf('%02dh', $i);
            $hourlyViews[$label] = $rawHourly[$i] ?? 0;
        }

        // 4. Top Páginas mais visitadas
        $topPages = Tracker::where('created_at', '>=', $startDate)
            ->select('path', DB::raw('count(*) as total'))
            ->groupBy('path')
            ->orderByDesc('total')
            ->limit($topPagesLimit)
            ->get();

        // 5. Origens de Tráfego (Referrers)
        $topReferrers = Tracker::where('created_at', '>=', $startDate)
            ->whereNotNull('referrer_host')
            ->select('referrer_host', DB::raw('count(*) as total'))
            ->groupBy('referrer_host')
            ->orderByDesc('total')
            ->limit($topReferrersLimit)
            ->get();

        // 6. Dispositivos (% Mobile vs Desktop)
        $devices = Tracker::where('created_at', '>=', $startDate)
            ->select('device', DB::raw('count(*) as total'))
            ->groupBy('device')
            ->pluck('total', 'device');

        // 7. NOVO: Top Eventos de Conversão / Cliques
        $topEvents = TrackerEvent::where('created_at', '>=', $startDate)
            ->select('event_name', DB::raw('count(*) as total'), DB::raw('count(distinct visitor_hash) as unique_users'))
            ->groupBy('event_name')
            ->orderByDesc('total')
            ->limit($topEventsLimit)
            ->get();

        // Top Países
        $topCountries = Tracker::where('created_at', '>=', $startDate)
            ->whereNotNull('country_name')
            ->select('country_name', 'country_code', DB::raw('count(*) as total'))
            ->groupBy('country_name', 'country_code')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Top Cidades
        $topCities = Tracker::where('created_at', '>=', $startDate)
            ->whereNotNull('city_name')
            ->select('city_name', 'region_name', DB::raw('count(*) as total'))
            ->groupBy('city_name', 'region_name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Verifica a dependência do pacote
        if (!class_exists(\Stevebauman\Location\Facades\Location::class)) {
            session()->now('warning', 'O recurso de geolocalização está inativo. Instale o pacote <code>stevebauman/location</code> para exibir dados de Cidade e País.');
        }

        return view('tracker::dashboard', compact(
            'days',
            'topPagesLimit',
            'topReferrersLimit',
            'topEventsLimit',
            'totalViews',
            'uniqueVisitors',
            'dailyViews',
            'hourlyViews',
            'topPages',
            'topReferrers',
            'devices',
            'topEvents',
            'topCountries',
            'topCities'
        ));
    }

    /**
     * Relatório Detalhado: Todos os Eventos de Conversão
     */
    public function events(Request $request)
    {
        $days = (int) $request->input('days', 30);
        $startDate = now()->subDays($days)->startOfDay();
        $search = $request->input('search');

        $query = TrackerEvent::where('created_at', '>=', $startDate)
            ->select('event_name', 'event_category', DB::raw('count(*) as total'), DB::raw('count(distinct visitor_hash) as unique_users'))
            ->when($search, fn($q) => $q->where('event_name', 'like', "%{$search}%"))
            ->groupBy('event_name', 'event_category')
            ->orderByDesc('total');

        $events = $query->paginate(25)->withQueryString();

        return view('tracker::events', compact('events', 'days', 'search'));
    }

    public function hourly(Request $request)
    {
        $days = (int) $request->input('days', setting('pluginSettings.Tracker.hourlyDays', 30));
        $startDate = now()->subDays($days)->startOfDay();

        $rawHourly = Tracker::where('created_at', '>=', $startDate)
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as total'))
            ->groupBy('hour')
            ->pluck('total', 'hour')
            ->toArray();

        $hourlyData = [];
        $totalPeriodViews = 0;

        for ($i = 0; $i < 24; $i++) {
            $label = sprintf('%02dh', $i);
            $count = $rawHourly[$i] ?? 0;
            $hourlyData[$label] = $count;
            $totalPeriodViews += $count;
        }

        $peakHour = array_search(max($hourlyData), $hourlyData);

        return view('tracker::hourly', compact('days', 'hourlyData', 'totalPeriodViews', 'peakHour'));
    }

    public function pages(Request $request)
    {
        $days = (int) $request->input('days', 30);
        $startDate = now()->subDays($days)->startOfDay();
        $search = $request->input('search');

        $query = Tracker::where('created_at', '>=', $startDate)
            ->select('path', DB::raw('count(*) as total'), DB::raw('count(distinct visitor_hash) as unique_visitors'))
            ->when($search, fn($q) => $q->where('path', 'like', "%{$search}%"))
            ->groupBy('path')
            ->orderByDesc('total');

        $pages = $query->paginate(25)->withQueryString();

        return view('tracker::pages', compact('pages', 'days', 'search'));
    }

    public function referrers(Request $request)
    {
        $days = (int) $request->input('days', 30);
        $startDate = now()->subDays($days)->startOfDay();
        $search = $request->input('search');

        $query = Tracker::where('created_at', '>=', $startDate)
            ->whereNotNull('referrer_host')
            ->select('referrer_host', DB::raw('count(*) as total'), DB::raw('count(distinct visitor_hash) as unique_visitors'))
            ->when($search, fn($q) => $q->where('referrer_host', 'like', "%{$search}%"))
            ->groupBy('referrer_host')
            ->orderByDesc('total');

        $referrers = $query->paginate(25)->withQueryString();

        return view('tracker::referrers', compact('referrers', 'days', 'search'));
    }
}
