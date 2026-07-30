<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-archive class="lucid-icon" /> Gerenciador de Backups
        </h3>
        <p>
            Crie backups completos ou modulares do seu site contendo o banco de dados, mídias enviadas, plugins e temas.
        </p>
    </header>

    <h4>Geração de Backups</h4>
    <p>
        Clique em <strong>"Novo Backup"</strong> para selecionar quais partes do sistema você deseja incluir no arquivo compactado (.zip).
    </p>

    <h4>Restauração em 1 Clique</h4>
    <p>
        Ao restaurar um arquivo de backup, o sistema substituirá os dados do banco e mídias para espelhar a cópia salva.
    </p>

    <h4>Comando para Automações (Cron)</h4>
    <p>Você pode configurar rotinas de backup automático no servidor executando no terminal:</p>
    <div class="code">
        php artisan backup:run
    </div>
</div>
