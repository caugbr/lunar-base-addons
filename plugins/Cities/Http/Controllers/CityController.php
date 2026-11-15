<?php

namespace Plugins\Cities\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Plugins\Cities\Models\City;

class CityController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $rawQuery = trim((string) $request->query('q', ''));
        $country = strtoupper(trim((string) $request->query('country', '')));
        $limit = max(1, min(25, (int) $request->query('limit', 12)));

        $asciiQuery = Str::ascii(mb_strtolower($rawQuery));
        $asciiQuery = preg_replace('/\s+/', ' ', $asciiQuery ?? '');

        if (mb_strlen($asciiQuery) < 2) {
            return response()->json(['data' => []]);
        }

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $asciiQuery);

        $prefixQuery = City::query()
            ->select(['id', 'city', 'city_ascii', 'admin_name', 'country', 'iso2', 'lat', 'lng', 'timezone', 'population'])
            ->where('city_ascii', 'like', "{$escaped}%");

        if ($country !== '' && strlen($country) === 2) {
            $prefixQuery->where('iso2', $country);
        }

        $results = $prefixQuery
            ->orderByDesc('population')
            ->limit($limit)
            ->get();

        // Completa com busca parcial caso o prefixo retorne menos itens que o limite
        if ($results->count() < $limit && mb_strlen($asciiQuery) >= 3) {
            $remaining = $limit - $results->count();
            $foundIds = $results->pluck('id')->all();

            $containsQuery = City::query()
                ->select(['id', 'city', 'city_ascii', 'admin_name', 'country', 'iso2', 'lat', 'lng', 'timezone', 'population'])
                ->where('city_ascii', 'like', "%{$escaped}%");

            if (!empty($foundIds)) {
                $containsQuery->whereNotIn('id', $foundIds);
            }

            if ($country !== '' && strlen($country) === 2) {
                $containsQuery->where('iso2', $country);
            }

            $extra = $containsQuery
                ->orderByDesc('population')
                ->limit($remaining)
                ->get();

            $results = $results->concat($extra);
        }

        $payload = $results->map(function (City $item) {
            $adminPart = $item->admin_name ? ", {$item->admin_name}" : '';

            return [
                'id'         => $item->id,
                'city'       => $item->city,
                'city_ascii' => $item->city_ascii,
                'admin_name' => $item->admin_name,
                'country'    => $item->country,
                'iso2'       => $item->iso2,
                'lat'        => $item->lat,
                'lng'        => $item['lng'],
                'timezone'   => $item->timezone,
                'label'      => "{$item->city_ascii}{$adminPart} - {$item->country}",
            ];
        });

        return response()->json(['data' => $payload]);
    }
}
