<?php

namespace Plugins\Countdown;

use Illuminate\Support\ServiceProvider;
use App\Services\EditorManager;

class CountdownServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // 1. Carrega as views (para a tela de Help)
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'countdown');

        // 2. Registra os assets no Editor Tiptap (Admin)
        EditorManager::registerScript(asset('plugins/countdown/js/countdown-block.js'));
        EditorManager::registerStyle(asset('plugins/countdown/css/countdown-editor.css'));

        // 3. Enfileira o CSS e o JS do relógio no site público
        add_style('countdown-front-css', asset('plugins/countdown/css/countdown-front.css'), [], '1.0.0');
        add_script('countdown-front-js', asset('plugins/countdown/js/countdown-front.js'), [], '1.0.0', true, true);
    }
}
