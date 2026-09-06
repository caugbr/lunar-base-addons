<?php

namespace Plugins\ArticleReader;

use Illuminate\Support\ServiceProvider;
use App\Support\HookManager;
use App\Support\Settings;

class ArticleReaderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $routesFile = __DIR__ . '/routes.php';
        if (file_exists($routesFile)) {
            $this->loadRoutesFrom($routesFile);
        }
    }

    public function boot(): void
    {
        // Carrega as views do plugin
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'article-reader');

        // Enfileira os assets no front-end público
        add_style('article-reader-css', asset('plugins/article-reader/css/reader.css'), [], '1.0.0');
        add_script('article-reader-js', asset('plugins/article-reader/js/reader.js'), [], '1.0.0', true, true);

        Settings::add([
            'type'  => 'subtitle',
            'icon'  => 'headphones',
            'label' => 'Configurações do Article Reader',
        ], 'reading');

        Settings::add([
            'key'         => 'reader_places',
            'type'        => 'checkbox',
            'label'       => 'Adicionar leitor a posts e páginas',
            'default'     => ['post', 'page'],
            'options'     => ['post' => 'Posts', 'page' => 'Páginas'],
        ], 'reading');

        // Callback único que atende tanto Página quanto Post
        $renderPlayer = function ($params = []) {
            $item = $params['page'] ?? $params['post'] ?? null;
            if (!$item || empty($item->content)) return '';

            return view('article-reader::player', ['item' => $item])->render();
        };

        // Registra nos dois hooks
        $places = setting('reading.reader_places', ['post', 'page']);
        if (is_string($places)) {
            $places = array_filter(array_map('trim', explode(',', $places)));
        }

        if (is_array($places) && in_array('page', $places)) {
            HookManager::register('page.before_content', $renderPlayer, 'Article Reader Plugin');
        }
        if (is_array($places) && in_array('post', $places)) {
            HookManager::register('post.before_content', $renderPlayer, 'Article Reader Plugin');
        }
    }
}
