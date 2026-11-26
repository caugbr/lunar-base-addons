<?php

namespace Plugins\Astrology\Console\Commands;

use Illuminate\Console\Command;
use Plugins\Astrology\Services\ThemeManager;
use Throwable;

class SyncThemesCommand extends Command
{
    protected $signature = 'astrology:sync';

    protected $description = 'Sincroniza os temas astrológicos em disco e gera o arquivo manifest.json.';

    public function handle(): int
    {
        try {
            $result = ThemeManager::syncManifest();
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }

        $publicCount = count($result['public']);
        $privateCount = count($result['private']);

        $this->info('Temas sincronizados com sucesso!');
        $this->line(" - Públicos ({$publicCount}): " . ($publicCount ? implode(', ', $result['public']) : 'nenhum'));
        $this->line(" - Privados ({$privateCount}): " . ($privateCount ? implode(', ', $result['private']) : 'nenhum'));
        $this->line("Manifesto salvo em: {$result['manifestPath']}");

        return self::SUCCESS;
    }
}
