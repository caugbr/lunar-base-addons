<?php

namespace Plugins\Tracker\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Plugins\Tracker\Models\Tracker;

class TrackerController extends Controller
{
    public function index()
    {
        $startDate = now()->subDays(30)->startOfDay();

        // 1. Total de visualizações e visitantes únicos nos últimos 30 dias
        $totalViews = Tracker::where('created_at', '>=', $startDate)->count();
        $uniqueVisitors = Tracker::where('created_at', '>=', $startDate)->distinct('visitor_hash')->count('visitor_hash');

        // 2. Gráfico diário de visualizações
        $dailyViews = Tracker::where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as views'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('views', 'date');

        // 3. Top 10 Páginas mais visitadas
        $topPages = Tracker::where('created_at', '>=', $startDate)
            ->select('path', DB::raw('count(*) as total'))
            ->groupBy('path')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // 4. Origens de Tráfego (Referrers)
        $topReferrers = Tracker::where('created_at', '>=', $startDate)
            ->whereNotNull('referrer_host')
            ->select('referrer_host', DB::raw('count(*) as total'))
            ->groupBy('referrer_host')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // 5. Dispositivos (% Mobile vs Desktop)
        $devices = Tracker::where('created_at', '>=', $startDate)
            ->select('device', DB::raw('count(*) as total'))
            ->groupBy('device')
            ->pluck('total', 'device');

        return view('tracker::dashboard', compact(
            'totalViews',
            'uniqueVisitors',
            'dailyViews',
            'topPages',
            'topReferrers',
            'devices'
        ));
    }
}
