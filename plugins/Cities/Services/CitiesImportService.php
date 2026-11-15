<?php

namespace Plugins\Cities\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class CitiesImportService
{
    public function import(bool $fresh = false): int
    {
        if ($fresh) {
            DB::table('cities')->truncate();
        } elseif (DB::table('cities')->exists()) {
            return 0;
        }

        $gzPath = dirname(__DIR__) . '/database/data/cities.csv.gz';
        $csvPath = dirname(__DIR__) . '/database/data/cities.csv';

        if (file_exists($gzPath)) {
            $handle = fopen('compress.zlib://' . $gzPath, 'rb');
            $usedPath = $gzPath;
        } elseif (file_exists($csvPath)) {
            $handle = fopen($csvPath, 'rb');
            $usedPath = $csvPath;
        } else {
            throw new RuntimeException("Nenhum arquivo de carga encontrado (esperado {$gzPath} ou {$csvPath}).");
        }

        if ($handle === false) {
            throw new RuntimeException("Não foi possível abrir o arquivo: {$usedPath}");
        }

        DB::disableQueryLog();

        $header = fgetcsv($handle, 0, ',', '"', '\\');
        if (!is_array($header)) {
            fclose($handle);
            throw new RuntimeException('Cabeçalho inválido no arquivo de cidades.');
        }

        // Remove eventual BOM UTF-8 do primeiro nome de coluna
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

        $cleanNull = static function (?string $val): ?string {
            if ($val === null || $val === '' || strtoupper($val) === 'NULL') {
                return null;
            }
            return $val;
        };

        $now = now()->toDateTimeString();
        $batch = [];
        $batchSize = 500;
        $imported = 0;

        DB::transaction(function () use ($handle, $header, $cleanNull, $now, &$batch, $batchSize, &$imported) {
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                if (count($row) !== count($header)) {
                    continue;
                }

                $item = array_combine($header, $row);
                $population = $cleanNull($item['population'] ?? null);

                $batch[] = [
                    'id'         => (int) $item['id'],
                    'city'       => $item['city'],
                    'city_ascii' => $item['city_ascii'],
                    'lat'        => (float) $item['lat'],
                    'lng'        => (float) $item['lng'],
                    'country'    => $item['country'],
                    'iso2'       => $cleanNull($item['iso2'] ?? null),
                    'iso3'       => $cleanNull($item['iso3'] ?? null),
                    'timezone'   => $cleanNull($item['timezone'] ?? null),
                    'admin_name' => $cleanNull($item['admin_name'] ?? null),
                    'capital'    => $cleanNull($item['capital'] ?? null),
                    'population' => $population !== null ? (int) $population : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $imported++;

                if (count($batch) >= $batchSize) {
                    DB::table('cities')->insert($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                DB::table('cities')->insert($batch);
            }
        });

        fclose($handle);

        return $imported;
    }
}
