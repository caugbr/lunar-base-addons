<?php

namespace Plugins\Heatmap\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HeatmapApiController extends Controller
{
    /**
     * Recebe cliques, hover e profundidade de rolagem
     */
    public function track(Request $request)
    {
        $data = $request->json()->all();

        $path          = $data['path'] ?? '/';
        $interactions  = $data['interactions'] ?? [];
        $scrollPercent = isset($data['scroll_percent']) ? (int) $data['scroll_percent'] : null;

        $now = now();

        // 1. Grava Cliques e Hover
        if (!empty($interactions) && is_array($interactions)) {
            $rows = [];
            foreach (array_slice($interactions, 0, 50) as $item) {
                $selector = trim($item['selector'] ?? '');
                $type     = in_array($item['type'] ?? '', ['click', 'hover'], true) ? $item['type'] : 'click';

                if (!empty($selector)) {
                    $rows[] = [
                        'url_path'   => substr($path, 0, 255),
                        'type'       => $type,
                        'selector'   => substr($selector, 0, 500),
                        'created_at' => $now,
                    ];
                }
            }

            if (!empty($rows)) {
                DB::table('heatmap_clicks')->insert($rows);
            }
        }

        // 2. Grava a Profundidade de Rolagem (Scroll)
        if ($scrollPercent !== null && $scrollPercent >= 0 && $scrollPercent <= 100) {
            DB::table('heatmap_scrolls')->insert([
                'url_path'    => substr($path, 0, 255),
                'max_percent' => $scrollPercent,
                'created_at'  => $now,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
