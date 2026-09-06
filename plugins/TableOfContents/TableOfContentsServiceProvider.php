<?php

namespace Plugins\TableOfContents;

use Illuminate\Support\ServiceProvider;
use App\Services\EditorManager;

class TableOfContentsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Registra o script e o CSS do bloco no editor do Lunar Base
        EditorManager::registerScript(asset('plugins/table-of-contents/js/toc-block.js'));
        EditorManager::registerStyle(asset('plugins/table-of-contents/css/toc.css'));

        // 2. CSS do Site Público (Usando o helper nativo do Lunar Base!)
        add_style('lunar-toc-front', asset('plugins/table-of-contents/css/toc-front.css'));
    }
}
