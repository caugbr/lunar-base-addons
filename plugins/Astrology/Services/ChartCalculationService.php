<?php

namespace Plugins\Astrology\Services;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Plugins\Astrology\Models\NatalChart;

class ChartCalculationService
{
    public function calculate(array $params): array
    {
        $payload = $this->buildCalculationPayload($params);
        $chartData = $this->dispatchCalculation($payload);

        return $this->normalizeCalculatedData($chartData);
    }

    public function calculateEphemeral(array $params): array
    {
        $payload = $this->buildCalculationPayload(array_merge($params, [
            'aspects' => $params['aspects'] ?? false,
        ]));

        $chartData = $this->dispatchCalculation($payload);

        return $this->normalizeCalculatedData($chartData);
    }

    public function calculateSolarReturn(NatalChart $natalChart, int $targetYear, array $overrides = []): array
    {
        if (!$natalChart->isCompleted() || empty($natalChart->planets['Sun']['longitude'])) {
            throw new Exception('O mapa natal base precisa estar calculado para levantar a Revolução Solar.');
        }

        $lat = (float) ($overrides['latitude'] ?? $natalChart->latitude);
        $lon = (float) ($overrides['longitude'] ?? $natalChart->longitude);
        $tz = (string) ($overrides['timezone'] ?? $natalChart->timezone ?? 'America/Sao_Paulo');
        $houseSystem = (string) ($overrides['house_system'] ?? $natalChart->house_system ?? 'placidus');
        $includeMinors = (bool) ($overrides['include_minors'] ?? $natalChart->include_minors);
        $orbGeneral = (float) ($overrides['orb_general'] ?? $natalChart->orb_general ?? 6.0);
        $orbLuminaries = (float) ($overrides['orb_luminaries'] ?? $natalChart->orb_luminaries ?? 10.0);

        $cacheKey = sprintf(
            'astrology_sr_%d_%d_%.5F_%.5F_%s_%s_%d',
            $natalChart->id,
            $targetYear,
            $lat,
            $lon,
            md5($tz),
            $houseSystem,
            $includeMinors ? 1 : 0
        );

        return Cache::remember($cacheKey, now()->addHours(6), function () use (
            $natalChart,
            $targetYear,
            $lat,
            $lon,
            $tz,
            $houseSystem,
            $includeMinors,
            $orbGeneral,
            $orbLuminaries
        ) {
            $payload = [
                'mode'                 => 'solar_return',
                'birth_date'           => $natalChart->birth_date->format('Y-m-d'),
                'target_sun_longitude' => (float) $natalChart->planets['Sun']['longitude'],
                'target_year'          => $targetYear,
                'latitude'             => $lat,
                'longitude'            => $lon,
                'timezone'             => $tz,
                'houseSystem'          => $houseSystem,
                'planets'              => 'all',
                'include_minors'       => $includeMinors,
                'orb_general'          => $orbGeneral,
                'orb_luminaries'       => $orbLuminaries,
            ];

            [$endpoint, $token] = $this->resolveEndpointConfig();

            $chartData = empty($endpoint)
                ? $this->callLocalSwetest('calcSolarReturn', $payload)
                : $this->requestRemoteEndpoint($endpoint, $token, $payload);

            return $this->normalizeCalculatedData($chartData);
        });
    }

    protected function buildCalculationPayload(array $params): array
    {
        $rawTime = trim((string) ($params['birth_time'] ?? $params['time'] ?? '12:00'));
        $formattedTime = strlen($rawTime) >= 8 ? substr($rawTime, 0, 8) : substr($rawTime, 0, 5);

        return [
            'date'           => $params['birth_date'] ?? $params['date'],
            'time'           => $formattedTime,
            'timezone'       => $params['timezone'] ?? 'America/Sao_Paulo',
            'latitude'       => (float) $params['latitude'],
            'longitude'      => (float) $params['longitude'],
            'houseSystem'    => $params['house_system'] ?? $params['houseSystem'] ?? 'placidus',
            'planets'        => $params['planets'] ?? 'all',
            'aspects'        => $params['aspects'] ?? true,
            'include_minors' => (bool) ($params['include_minors'] ?? false),
            'orb_general'    => (float) ($params['orb_general'] ?? 6.0),
            'orb_luminaries' => (float) ($params['orb_luminaries'] ?? 10.0),
        ];
    }

    protected function dispatchCalculation(array $payload): array
    {
        [$endpoint, $token] = $this->resolveEndpointConfig();

        if (empty($endpoint)) {
            return $this->callLocalSwetest('calc', $payload);
        }

        return $this->requestRemoteEndpoint($endpoint, $token, $payload);
    }

    protected function resolveEndpointConfig(): array
    {
        $endpoint = config('pluginSettings.Astrology.apiEndpoint')
            ?: config('services.swetest.url')
            ?: env('SWETEST_REMOTE_URL');

        $token = config('pluginSettings.Astrology.apiToken')
            ?: config('services.swetest.token')
            ?: env('SWETEST_API_TOKEN');

        return [$endpoint, $token];
    }

    protected function requestRemoteEndpoint(string $endpoint, ?string $token, array $payload): array
    {
        try {
            $request = Http::timeout(15);

            if (!empty($token)) {
                $request->withToken($token);
            }

            $response = $request->post($endpoint, $payload);
        } catch (ConnectionException $e) {
            throw new Exception('Falha de conexão com o serviço remoto de efemérides: ' . $e->getMessage(), 0, $e);
        }

        if ($response->failed()) {
            $errorMessage = $response->json('error') ?? 'Erro HTTP ' . $response->status() . ' retornado pelo serviço remoto.';
            throw new Exception($errorMessage);
        }

        $data = $response->json();
        if (!is_array($data) || empty($data['planets'])) {
            throw new Exception('A resposta do serviço de efemérides não contém a estrutura esperada de planetas.');
        }

        return $data;
    }

    protected function normalizeCalculatedData(array $chartData): array
    {
        if (isset($chartData['planets'])) {
            $chartData['planets'] = $this->normalizeLunarNodes($chartData['planets']);
        }

        return $chartData;
    }

    protected function normalizeLunarNodes(array $planets): array
    {
        $nodeKey = null;
        foreach (['North Node', 'True Node', 'mean Node'] as $candidate) {
            if (isset($planets[$candidate])) {
                $nodeKey = $candidate;
                break;
            }
        }

        if (!$nodeKey) {
            return $planets;
        }

        $northNode = $planets[$nodeKey];
        $planets['North Node'] = $northNode;
        unset($planets['True Node'], $planets['mean Node']);

        $southLongitude = fmod((float) $northNode['longitude'] + 180.0, 360.0);
        $degreesInSign = fmod($southLongitude, 30.0);
        $degrees = (int) floor($degreesInSign);
        $minutes = (int) round(($degreesInSign - $degrees) * 60);

        $planets['South Node'] = [
            'longitude'     => round($southLongitude, 6),
            'formatted'     => sprintf("%02d° %02d'", $degrees, $minutes),
            'is_retrograde' => $northNode['is_retrograde'] ?? true,
            'daily_speed'   => $northNode['daily_speed'] ?? 0,
        ];

        return $planets;
    }

    public function extractSummarySigns(array $chartData): array
    {
        return [
            'sun_sign'       => $chartData['planets']['Sun']['sign'] ?? null,
            'moon_sign'      => $chartData['planets']['Moon']['sign'] ?? null,
            'ascendant_sign' => $chartData['angles']['Ascendant']['sign'] ?? null,
        ];
    }

    protected function callLocalSwetest(string $method, array $payload): array
    {
        $serviceClass = \Plugins\Swetest\Services\SwissChartService::class;

        if (!class_exists($serviceClass)) {
            throw new Exception('Endpoint remoto não configurado e o plugin Swetest local não está ativo.');
        }

        return $serviceClass::$method($payload);
    }
}
