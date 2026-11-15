<?php

namespace Plugins\Cities\Console;

use Illuminate\Console\Command;
use Plugins\Cities\Services\CitiesImportService;

class SeedCitiesCommand extends Command
{
    protected $signature = 'cities:seed {--fresh : Limpa a tabela cities antes de importar}';
    protected $description = 'Popula a tabela cities a partir do arquivo cities.csv.gz do plugin';

    public function handle(CitiesImportService $importer): int
    {
        $fresh = (bool) $this->option('fresh');

        $count = $importer->import($fresh);

        if ($count === 0) {
            $this->info('A tabela cities já está populada. Use --fresh para reimportar.');
            return self::SUCCESS;
        }

        $this->info("{$count} cidades importadas com sucesso.");

        return self::SUCCESS;
    }
}
