<?php

namespace Plugins\Astrology\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Plugins\Astrology\Models\NatalChart;
use Plugins\Astrology\Services\ChartCalculationService;
use Throwable;

class CalculateNatalChartJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public int $backoff = 10;

    public function __construct(public NatalChart $chart)
    {
    }

    public function handle(ChartCalculationService $calculationService): void
    {
        $this->chart->update([
            'status'        => 'processing',
            'error_message' => null,
        ]);

        $params = [
            'birth_date'     => $this->chart->birth_date->format('Y-m-d'),
            'birth_time'     => $this->chart->birth_time,
            'timezone'       => $this->chart->timezone,
            'latitude'       => $this->chart->latitude,
            'longitude'      => $this->chart->longitude,
            'house_system'   => $this->chart->house_system,
            'include_minors' => $this->chart->include_minors,
            'orb_general'    => $this->chart->orb_general,
            'orb_luminaries' => $this->chart->orb_luminaries,
        ];

        $chartData = $calculationService->calculate($params);
        $summary = $calculationService->extractSummarySigns($chartData);

        $this->chart->update([
            'chart_data'     => $chartData,
            'sun_sign'       => $summary['sun_sign'],
            'moon_sign'      => $summary['moon_sign'],
            'ascendant_sign' => $summary['ascendant_sign'],
            'status'         => 'completed',
            'error_message'  => null,
        ]);

        // Limpa qualquer cache de visualizacao preexistente deste mapa
        Cache::forget("natal_chart_svg_{$this->chart->id}");
    }

    public function failed(Throwable $exception): void
    {
        $this->chart->update([
            'status'        => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
