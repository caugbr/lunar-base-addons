<?php

namespace Plugins\Swetest\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugins\Swetest\Services\SwissChartService;
use Throwable;

class SwetestApiController extends Controller
{
    public function calculate(Request $request): JsonResponse
    {
        if (!config('pluginSettings.Swetest.exposeApiEndpoint', false)) {
            abort(404);
        }

        $authResult = $this->authenticate($request);
        if ($authResult instanceof JsonResponse) {
            return $authResult;
        }

        $input = $request->all();
        $mode = $input['mode'] ?? 'chart';

        if ($mode === 'solar_return') {
            if (empty($input['target_year']) || empty($input['birth_date'])) {
                return response()->json([
                    'error' => 'Os campos "target_year" e "birth_date" são obrigatórios para Revolução Solar.',
                ], 400);
            }
        } elseif (empty($input['date'])) {
            return response()->json([
                'error' => 'O campo "date" (formato YYYY-MM-DD) é obrigatório.',
            ], 400);
        }

        try {
            $sharedParams = [
                'timezone'       => $input['timezone'] ?? 'UTC',
                'latitude'       => (isset($input['latitude']) && $input['latitude'] !== '') ? (float) $input['latitude'] : null,
                'longitude'      => (isset($input['longitude']) && $input['longitude'] !== '') ? (float) $input['longitude'] : null,
                'houseSystem'    => $input['houseSystem'] ?? 'placidus',
                'planets'        => $input['planets'] ?? 'all',
                'aspects'        => array_key_exists('aspects', $input) ? (bool) $input['aspects'] : true,
                'include_minors' => !empty($input['include_minors']),
                'orb_general'    => isset($input['orb_general']) ? (float) $input['orb_general'] : 6.0,
                'orb_luminaries' => isset($input['orb_luminaries']) ? (float) $input['orb_luminaries'] : 10.0,
            ];

            if ($mode === 'solar_return') {
                $chart = SwissChartService::calcSolarReturn(array_merge($sharedParams, [
                    'birth_date'           => $input['birth_date'],
                    'birth_time'           => $input['birth_time'] ?? '12:00:00',
                    'birth_timezone'       => $input['birth_timezone'] ?? ($input['timezone'] ?? 'UTC'),
                    'target_sun_longitude' => (isset($input['target_sun_longitude']) && $input['target_sun_longitude'] !== '')
                        ? (float) $input['target_sun_longitude']
                        : null,
                    'target_year'          => (int) $input['target_year'],
                ]));
            } else {
                $chart = SwissChartService::calc(array_merge($sharedParams, [
                    'date' => $input['date'],
                    'time' => $input['time'] ?? '12:00',
                ]));
            }

            return response()->json($chart);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function authenticate(Request $request): string|JsonResponse
    {
        $entries = config('pluginSettings.Swetest.tokens', []);

        if (!is_array($entries) || empty($entries)) {
            return response()->json(['error' => 'Nenhum token configurado no plugin Swetest.'], 500);
        }

        $activeEntries = array_filter($entries, static function ($entry): bool {
            return is_array($entry)
                && ($entry['active'] ?? true) === true
                && !empty($entry['token']);
        });

        if (empty($activeEntries)) {
            return response()->json(['error' => 'Nenhum token ativo configurado no plugin Swetest.'], 500);
        }

        $providedToken = (string) $request->bearerToken();
        if ($providedToken === '') {
            return response()->json(['error' => 'Não autorizado. Token Bearer ausente.'], 401);
        }

        $matchedClient = null;

        foreach ($activeEntries as $entry) {
            if (hash_equals((string) $entry['token'], $providedToken)) {
                $matchedClient = (string) ($entry['client'] ?? 'default');
            }
        }

        if ($matchedClient === null) {
            return response()->json(['error' => 'Não autorizado. Token Bearer inválido.'], 401);
        }

        return $matchedClient;
    }
}
