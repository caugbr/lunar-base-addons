<?php

namespace Plugins\ArchiveVault\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Plugins\ArchiveVault\Models\ArchivedRecord;
use App\Models\Post;
use App\Models\Page;
use App\Models\PostMeta;

class ArchiveVaultController extends Controller
{
    public function index(Request $request)
    {
        // Trava de segurança: se a tabela ainda não existe no banco
        if (function_exists('dbAvailable') && !dbAvailable('archived_records')) {
            return view('archive-vault::index', [
                'tableAvailable' => false,
                'records'        => collect(),
                'counts'         => ['all' => 0, 'posts' => 0, 'pages' => 0]
            ]);
        }

        $query = ArchivedRecord::latest('purged_at');

        if ($request->filled('type')) {
            $class = $request->type === 'post' ? Post::class : Page::class;
            $query->where('archivable_type', $class);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $records = $query->paginate(setting('reading.pagination_max_items', 15))->withQueryString();

        $counts = [
            'all'   => ArchivedRecord::count(),
            'posts' => ArchivedRecord::where('archivable_type', Post::class)->count(),
            'pages' => ArchivedRecord::where('archivable_type', Page::class)->count(),
        ];

        $tableAvailable = true;

        return view('archive-vault::index', compact('records', 'counts', 'tableAvailable'));
    }

    public function show(ArchivedRecord $record)
    {
        return view('archive-vault::show', compact('record'));
    }

    /**
     * Restaura o registro direto para a tabela de Posts ou Pages
     */
    public function restore(ArchivedRecord $record)
    {
        $payload = $record->payload;
        $class = $record->archivable_type;

        // Trata duplicidade de slug
        $slug = $record->slug;
        while ($class::where('slug', $slug)->exists()) {
            $slug = $slug . '-restored-' . Str::random(4);
        }

        // Limpa campos que serão regerados
        unset($payload['id'], $payload['deleted_at'], $payload['created_at'], $payload['updated_at']);
        $payload['slug'] = $slug;
        $payload['status'] = 'draft'; // Volta sempre como rascunho por segurança

        $newModel = $class::create($payload);

        // Restaura termos de taxonomia
        if (!empty($payload['_term_ids'])) {
            $newModel->terms()->sync($payload['_term_ids']);
        }

        // Restaura metas de posts se existirem
        if ($class === Post::class && !empty($payload['_meta'])) {
            foreach ($payload['_meta'] as $k => $v) {
                PostMeta::create(['post_id' => $newModel->id, 'meta_key' => $k, 'meta_value' => $v]);
            }
        }

        // Remove do cofre após restaurar com sucesso
        $record->delete();

        log_admin("Registro ressuscitado do cofre: {$record->title}", "archive-vault");

        return redirect()->route('admin.archive_vault.index')
            ->with('success', "{$record->type_name} restaurada com sucesso como Rascunho!");
    }

    /**
     * Exclui em definitivo do cofre (Apenas Super Admin)
     */
    public function destroy(ArchivedRecord $record)
    {
        $title = $record->title;
        $record->delete();

        log_admin("Registro expurgado do cofre definitivamente: {$title}", "archive-vault");

        return redirect()->route('admin.archive_vault.index')
            ->with('success', 'Registro expurgado permanentemente do cofre.');
    }
}
