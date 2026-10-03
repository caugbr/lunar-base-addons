<?php

namespace Plugins\Comments;

use Illuminate\Support\ServiceProvider;
use App\Support\PublicationTypes;
use App\Support\AdminMenu;
use App\Support\HookManager;
use App\Support\Settings;
use Plugins\Comments\Models\Comment;

class CommentsServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $routesFile = __DIR__ . '/routes.php';
        if (file_exists($routesFile)) {
            require $routesFile;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Carrega Migrations e Views do plugin
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'comments');

        // 2. Registra o grupo de configurações e opções de comentários
        Settings::addGroup('comments', [
            'tab'         => 'Comentários',
            'title'       => 'Comentários',
            'description' => 'Configurações do sistema de comentários',
            'icon'        => 'message-square',
        ]);

        Settings::add([
            'key'         => 'comments_require_moderation',
            'type'        => 'switch',
            'label'       => 'Moderação obrigatória',
            'description' => 'Todos os comentários novos precisam ser aprovados manualmente antes de aparecerem no site.',
            'default'     => false,
            'active'      => 'Moderação ativa',
            'inactive'    => 'Sem moderação',
        ], 'comments');

        Settings::add([
            'key'         => 'pagination_items',
            'type'        => 'number',
            'label'       => 'Itens por página (moderação)',
            'description' => 'Quantidade de comentários exibidos por página na tela de moderação.',
            'default'     => 20,
            'attributes'  => ['min' => 5, 'max' => 100, 'step' => 5],
        ], 'comments');

        // Checkbox dinâmico alimentado pelo PublicationTypes
        Settings::add([
            'key'         => 'show_comments_in',
            'type'        => 'checkbox',
            'label'       => 'Mostrar comentários em',
            'description' => 'Marque onde quer que os comentários apareçam. Para tipos customizados, espera-se que haja o hook "tipo.comments".',
            'default'     => 'post',
            'options'     => PublicationTypes::labels(),
        ], 'comments');

        // 3. Normaliza a configuração lida (aceita array, string separada por vírgula ou string simples)
        $showInRaw = setting('comments.show_comments_in', 'post');
        if (is_array($showInRaw)) {
            $enabledTypes = $showInRaw;
        } elseif (is_string($showInRaw)) {
            $enabledTypes = array_map('trim', explode(',', $showInRaw));
        } else {
            $enabledTypes = ['post'];
        }

        // Callback padrão da relação polimórfica 'comments'
        $relationCallback = function ($model) {
            return $model->morphMany(Comment::class, 'commentable')
                ->whereNull('parent_id')
                ->where('status', 'approved')
                ->orderBy('created_at', 'desc');
        };

        // 4. LOOP DINÂMICO PARA CADA TIPO HABILITADO
        foreach ($enabledTypes as $typeKey) {
            $typeConfig = PublicationTypes::get($typeKey);
            if (!$typeConfig) {
                continue;
            }

            $modelClass = $typeConfig['model'] ?? null;
            $typeLabel  = $typeConfig['label'] ?? ucfirst($typeKey);

            // A. Injeta a relação polimórfica dinamicamente na Model correspondente
            if ($modelClass && class_exists($modelClass) && method_exists($modelClass, 'resolveRelationUsing')) {
                $modelClass::resolveRelationUsing('comments', $relationCallback);
            }

            // B. Injeta o submenu de comentários no menu correspondente da Admin
            AdminMenu::addSubItem($typeLabel, [
                'label'  => 'Comentários',
                'icon'   => 'message-square',
                'route'  => 'admin.comments.index',
                'params' => ['type' => $typeKey],
                'active' => 'admin.comments.*',
            ]);

            // C. Determina quais hooks escutar (retrocompatibilidade para post/page + convenção universal)
            $hooksToListen = [];
            if ($typeKey === 'post') {
                $hooksToListen[] = 'post.footer_end';
            } elseif ($typeKey === 'page') {
                $hooksToListen[] = 'page.after_content';
            } else {
                // Convenção universal para todos os tipos (ex: curso.comments, post.comments, etc.)
                $hooksToListen[] = "{$typeKey}.comments";
            }

            // D. Registra a renderização da área de comentários nos hooks determinados
            foreach (array_unique($hooksToListen) as $hookName) {
                HookManager::register($hookName, function ($params) use ($typeKey) {
                    // Tenta capturar a model pelo nome comum, pela chave do tipo ou primeiro item
                    $model = $params['model'] ?? $params[$typeKey] ?? reset($params);

                    if (!$model || !is_object($model)) {
                        return '';
                    }

                    // Checa se os comentários foram desativados individualmente para este registro
                    $noComments = false;
                    // Caso 1: Coluna direta no banco ou atributo da Model ($model->no_comments)
                    if (isset($model->no_comments)) {
                        $noComments = (bool) $model->no_comments;
                    }
                    // Caso 2: Coluna JSON 'meta' (como em Pages)
                    elseif (isset($model->meta) && is_array($model->meta)) {
                        $noComments = !empty($model->meta['no_comments']);
                    }
                    // Caso 3: Relação ou método meta() (como em Posts)
                    elseif (method_exists($model, 'meta')) {
                        $metaRecord = $model->meta()->where('meta_key', 'no_comments')->first();
                        $noComments = $metaRecord ? (bool) $metaRecord->meta_value : false;
                    }

                    if (!$noComments && view()->exists('comments::comments-area')) {
                        return view('comments::comments-area', ['model' => $model])->render();
                    }

                    return '';
                }, "Comments plugin ({$typeLabel})");
            }
        }

        // 5. Switches na Sidebar do Painel (Post e Page)
        $this->registerPropertySwitches($enabledTypes);
    }

    /**
     * Registra o switcher de 'Não exibir comentários' nas propriedades de Posts e Páginas
     */
    protected function registerPropertySwitches(array $enabledTypes): void
    {
        // Posts
        if (in_array('post', $enabledTypes, true)) {
            HookManager::register('admin.post_properties', function ($params) {
                $post = $params['post'] ?? null;
                $noComments = false;

                if ($post && method_exists($post, 'meta')) {
                    $meta = $post->meta()->where('meta_key', 'no_comments')->first();
                    $noComments = $meta ? (bool) $meta->meta_value : false;
                }

                return view('comments::partials.no-comments-switch', compact('noComments'))->render();
            }, 'Comments properties switch (Posts)');

            \App\Models\Post::saved(function ($post) {
                if (request()->has('title')) {
                    $noCommentsVal = request()->boolean('no_comments') ? '1' : '0';

                    app()->terminating(function () use ($post, $noCommentsVal) {
                        \App\Models\PostMeta::updateOrCreate(
                            ['post_id' => $post->id, 'meta_key' => 'no_comments'],
                            ['meta_value' => $noCommentsVal]
                        );
                    });
                }
            });
        }

        // Páginas
        if (in_array('page', $enabledTypes, true)) {
            HookManager::register('admin.page_properties', function ($params) {
                $page = $params['page'] ?? $params['model'] ?? null;
                $noComments = false;

                if ($page) {
                    $meta = is_string($page->meta) ? json_decode($page->meta, true) : $page->meta;
                    $noComments = !empty($meta['no_comments']);
                }

                return view('comments::partials.no-comments-switch', compact('noComments'))->render();
            }, 'Comments properties switch (Pages)');

            \App\Models\Page::saved(function ($page) {
                if (request()->has('title')) {
                    $rawPage = \Illuminate\Support\Facades\DB::table('pages')
                        ->where('id', $page->id)
                        ->select('meta')
                        ->first();

                    $metaData = [];
                    if ($rawPage && !empty($rawPage->meta)) {
                        $metaData = json_decode($rawPage->meta, true) ?? [];
                    }

                    $metaData['no_comments'] = request()->boolean('no_comments');

                    \Illuminate\Support\Facades\DB::table('pages')
                        ->where('id', $page->id)
                        ->update(['meta' => json_encode($metaData)]);
                }
            });
        }
    }
}
