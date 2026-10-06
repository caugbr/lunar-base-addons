<?php

namespace Plugins\Heatmap\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HeatmapAdminController extends Controller
{
    /**
     * Lista de páginas com cliques registrados
     */
    public function index(Request $request)
    {
        $days = $request->integer('days', 30);
        $pages = [];
        $tables_available = dbAvailable('heatmap_clicks') && dbAvailable('heatmap_scrolls');

        if ($tables_available) {
            $pages = DB::table('heatmap_clicks')
                ->select('url_path', DB::raw('count(*) as total_clicks'))
                ->when($days > 0, function ($q) use ($days) {
                    $q->where('created_at', '>=', now()->subDays($days));
                })
                ->groupBy('url_path')
                ->orderByDesc('total_clicks')
                ->paginate(setting('reading.pagination_max_items', 15));
        }

        return view('heatmap::admin.index', compact('tables_available', 'pages', 'days'));
    }

    /**
     * Tela de visualização do mapa (Iframe + Canvas)
     */
    public function show(Request $request)
    {
        $path = $request->query('path', '/');
        $days = $request->integer('days', 30);

        return view('heatmap::admin.view', compact('path', 'days'));
    }

    /**
     * Endpoint unificado que entrega dados de Cliques, Atenção (Hover) ou Rolagem (Scroll)
     */
    public function data(Request $request)
    {
        $path = $request->query('path', '/');
        $mode = $request->query('mode', 'click'); // 'click' | 'hover' | 'scroll'
        $days = $request->integer('days', 30);

        // =========================================================================
        // MODO 3: ROLAGEM / PROFUNDIDADE (SCROLL MAP)
        // =========================================================================
        if ($mode === 'scroll') {
            $query = DB::table('heatmap_scrolls')
                ->where('url_path', $path)
                ->when($days > 0, function ($q) use ($days) {
                    $q->where('created_at', '>=', now()->subDays($days));
                });

            $totalViews = $query->count();
            $average = $totalViews > 0 ? round($query->avg('max_percent')) : 0;

            // Calcula quantos % dos visitantes passaram de cada marco (10%, 25%, 50%, 75%, 90%, 100%)
            $distribution = [];
            if ($totalViews > 0) {
                $thresholds = [10, 25, 50, 75, 90, 100];
                foreach ($thresholds as $th) {
                    $reached = (clone $query)->where('max_percent', '>=', $th)->count();
                    $distribution[] = [
                        'percent_mark' => $th,
                        'visitors_reached' => $reached,
                        'rate' => round(($reached / $totalViews) * 100) // ex: 85% dos visitantes chegaram aqui
                    ];
                }
            }

            return response()->json([
                'mode'         => 'scroll',
                'total'        => $totalViews,
                'average'      => $average,
                'distribution' => $distribution,
            ]);
        }

        // =========================================================================
        // MODOS 1 e 2: CLIQUES OU ATENÇÃO/HOVER (CLICK & HOVER MAP)
        // =========================================================================
        $type = ($mode === 'hover') ? 'hover' : 'click';

        $elements = DB::table('heatmap_clicks')
            ->select('selector', DB::raw('count(*) as clicks'))
            ->where('url_path', $path)
            ->where('type', $type) // 👈 Filtra por 'click' ou 'hover'
            ->when($days > 0, function ($q) use ($days) {
                $q->where('created_at', '>=', now()->subDays($days));
            })
            ->groupBy('selector')
            ->orderByDesc('clicks')
            ->limit(500)
            ->get();

        $totalInteractions = $elements->sum('clicks');

        return response()->json([
            'mode'     => $type,
            'total'    => $totalInteractions,
            'elements' => $elements
        ]);
    }

    /**
     * Limpa os dados de uma página ou tudo
     */
    public function clear(Request $request)
    {
        $path = $request->input('path');

        if ($path) {
            DB::table('heatmap_clicks')->where('url_path', $path)->delete();
            DB::table('heatmap_scrolls')->where('url_path', $path)->delete();
            $msg = "Dados da página '{$path}' foram limpos com sucesso.";
        } else {
            DB::table('heatmap_clicks')->truncate();
            DB::table('heatmap_scrolls')->truncate();
            $msg = "Todos os dados de mapas de calor e rolagem foram zerados.";
        }

        return redirect()->route('admin.heatmap.index')->with('success', $msg);
    }
}
