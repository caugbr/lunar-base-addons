<?php

namespace Plugins\FileDownload;

use Illuminate\Support\ServiceProvider;
use App\Services\EditorManager;

class FileDownloadServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Injeta os assets do editor no painel admin
        EditorManager::registerScript(asset('plugins/file-download/js/file-download-block.js'));
        EditorManager::registerStyle(asset('plugins/file-download/css/file-download-editor.css'));

        // Enfileira os estilos para exibição correta no site público (não precisa de JS no front)
        add_style('file-download-front-css', asset('plugins/file-download/css/file-download-front.css'), [], '1.0.0');
    }
}
