<?php

namespace Plugins\Swetest\Console;

use Illuminate\Console\Command;
use Plugins\Swetest\Services\SwissChartService;
use Throwable;

class CheckSwetestCommand extends Command
{
    protected $signature = 'swetest:check';
    protected $description = 'Verifica disponibilidade de exec(), binário swetest e arquivos ephe';

    public function handle(): int
    {
        $service = new SwissChartService();
        $status = $service->checkEnvironment();

        $this->line('SO: ' . $status['os']);
        $this->line('exec(): ' . ($status['exec_available'] ? 'OK' : 'BLOQUEADO'));
        $this->line('Binário: ' . $status['swetest_path'] . ' (' . ($status['bin_exists'] ? 'existe' : 'ausente') . ', ' . ($status['bin_executable'] ? 'executável' : 'sem +x') . ')');
        $this->line('Efemérides: ' . $status['ephe_path'] . ' (' . ($status['ephe_exists'] ? 'existe' : 'ausente') . ')');

        if (!$status['ready']) {
            $this->error('Ambiente não está pronto para cálculo local.');
            return self::FAILURE;
        }

        try {
            $test = $service->execute([
                'date'     => '2000-01-01',
                'time'     => '12:00',
                'timezone' => 'UTC',
                'planets'  => 'sun',
                'aspects'  => false,
            ]);

            $sun = $test['planets']['Sun']['formatted'] ?? 'desconhecido';
            $this->info("Teste executado com sucesso. Sol em 01/01/2000 12:00 UTC: {$sun}");

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Falha ao executar teste: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
