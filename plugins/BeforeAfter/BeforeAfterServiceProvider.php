<?php

namespace Plugins\BeforeAfter;

use Illuminate\Support\ServiceProvider;
use App\Services\EditorManager;

class BeforeAfterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Injeta os assets do editor no painel admin
        EditorManager::registerScript(asset('plugins/before-after/js/before-after-block.js'));
        EditorManager::registerStyle(asset('plugins/before-after/css/before-after-editor.css'));

        // Enfileira os assets para exibição correta no site público
        add_style('before-after-front-css', asset('plugins/before-after/css/before-after-front.css'), [], '1.0.0');
        add_script('before-after-front-js', asset('plugins/before-after/js/before-after-front.js'), [], '1.0.0', true, true);
    }
}
