<?php

namespace Plugins\Backup\Console\Commands;

use Illuminate\Console\Command;
use Plugins\Backup\Helpers\BackupHelper;

class BackupRunCommand extends Command
{
    protected $signature = 'backup:run {--no-db} {--no-media} {--no-plugins} {--no-themes}';
    protected $description = 'Gera um backup completo do sistema Lunar Base';

    public function handle(): int
    {
        $this->info('🚀 Iniciando processo de backup do Lunar Base...');

        $options = [
            'include_db'      => !$this->option('no-db'),
            'include_media'   => !$this->option('no-media'),
            'include_plugins' => !$this->option('no-plugins'),
            'include_themes'  => !$this->option('no-themes'),
        ];

        try {
            $filename = BackupHelper::createBackup($options);
            $this->info("✅ Backup gerado com sucesso: {$filename}");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Falha no backup: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
