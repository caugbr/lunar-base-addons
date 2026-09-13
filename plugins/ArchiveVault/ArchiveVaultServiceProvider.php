<?php

namespace Plugins\ArchiveVault;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Support\AdminMenu;
use App\Support\HookManager;
use App\Models\Post;
use App\Models\Page;
use Plugins\ArchiveVault\Models\ArchivedRecord;

class ArchiveVaultServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // As rotas DEVEM ser sempre registradas (a proteção fica no middleware dentro de routes.php)
        $routesFile = __DIR__ . '/routes.php';
        if (file_exists($routesFile)) {
            $this->loadRoutesFrom($routesFile);
        }
    }

    public function boot(): void
    {
        // As migrations devem ser sempre carregadas para o 'php artisan migrate' funcionar
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'archive-vault');

        // Menu Admin (o próprio AdminMenu já filtra pelo 'role' => 'admin')
        if (class_exists(AdminMenu::class)) {
            AdminMenu::addSubItem('Ferramentas', [
                'label'      => 'Conteúdo excluído',
                'icon'       => 'archive-x',
                'route'      => 'admin.archive_vault.index',
                'active'     => 'admin.archive_vault.*',
                'role'       => 'admin',
            ]);
        }

        // Card de Ferramentas (aqui a checagem funciona porque roda na hora da view, com sessão ativa)
        if (class_exists(HookManager::class)) {
            HookManager::register('admin.tools_page', function($params) {
                if (!auth()->check() || !auth()->user()->hasRole('admin')) {
                    return '';
                }

                if (view()->exists('admin.tools.tool-card')) {
                    return view('admin.tools.tool-card', [
                        'icon'         => 'archive-x',
                        'title'        => 'Conteúdo excluído',
                        'text'         => 'Audite publicações excluídas',
                        'buttonTarget' => route('admin.archive_vault.index'),
                        'buttonLabel'  => 'Auditar',
                    ])->render();
                }
                return '';
            }, 'Plugin Archive Vault');
        }

        // Listeners Globais (precisam rodar sempre para nunca perder um snapshot)
        Event::listen('eloquent.forceDeleting: ' . Post::class, function (Post $post) {
            $this->captureSnapshot($post);
        });

        Event::listen('eloquent.forceDeleting: ' . Page::class, function (Page $page) {
            $this->captureSnapshot($page);
        });
    }

    /**
     * Tira a "fotografia" do modelo e salva no cofre
     */
    protected function captureSnapshot($model): void
    {
        if (function_exists('dbAvailable') && !dbAvailable('archived_records')) {
            return;
        }

        try {
            $data = $model->toArray();

            $data['_term_ids'] = $model->terms ? $model->terms->pluck('id')->toArray() : [];

            if ($model instanceof Post && method_exists($model, 'meta')) {
                $data['_meta'] = $model->meta ? $model->meta->pluck('meta_value', 'meta_key')->toArray() : [];
            }

            ArchivedRecord::create([
                'archivable_type'     => get_class($model),
                'original_id'         => $model->id,
                'title'               => $model->title ?? 'Sem título',
                'slug'                => $model->slug ?? null,
                'payload'             => $data,
                'original_created_at' => $model->created_at,
                'purged_at'           => now(),
            ]);

            log_admin(
                "Arquivo movido para o cofre permanente: '{$model->title}'",
                'archive-vault',
                ['type' => get_class($model), 'original_id' => $model->id]
            );

        } catch (\Throwable $e) {
            \Log::error("Erro ao salvar no ArchiveVault: " . $e->getMessage());
        }
    }
}
