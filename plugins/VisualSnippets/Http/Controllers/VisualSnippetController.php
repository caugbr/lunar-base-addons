<?php

namespace Plugins\VisualSnippets\Http\Controllers;

use App\Http\Controllers\Controller;
use Plugins\VisualSnippets\Models\VisualSnippet;
use Illuminate\Http\Request;

class VisualSnippetController extends Controller
{
    /**
     * Lista todos os Snippets cadastrados com paginação e busca.
     */
    public function index(Request $request)
    {
        // Guarda de segurança: se a migration ainda não rodou, não dispara SQL
        if (!dbAvailable('visual_snippets')) {
            return view('visual-snippets::admin.index', [
                'snippets' => collect()
            ]);
        }

        $query = VisualSnippet::query();

        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', $request->is_active);
        }

        $snippets = $query->orderBy('created_at', 'desc')
            ->paginate(setting('reading.pagination_max_items', 15));

        return view('visual-snippets::admin.index', compact('snippets'));
    }

    /**
     * Exibe a tela de criação do novo molde.
     */
    public function create()
    {
        return view('visual-snippets::admin.create');
    }

    /**
     * Salva o novo molde vindo do editor Tiptap.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'slug'          => 'required|string|alpha_dash|unique:visual_snippets,slug',
            'description'   => 'nullable|string|max:500',
            'content'       => 'required|string',
            'content_json'  => 'nullable|string',
            'payload'       => 'nullable|string',
            'thumbnail_url' => 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $validated['content_json'] = !empty($validated['content_json']) ? json_decode($validated['content_json'], true) : null;
        $validated['payload']      = !empty($validated['payload']) ? (json_decode($validated['payload'], true) ?? []) : [];
        $validated['is_active']    = $request->boolean('is_active', true);

        $validated['thumbnail_url'] = saveBase64Image($validated['thumbnail_url'] ?? null, 'snippets');

        VisualSnippet::create($validated);

        return redirect()->route('admin.visual-snippets.index')->with('success', 'Snippet visual criado com sucesso!');
    }

    /**
     * Exibe o formulário de edição do molde.
     */
    public function edit(VisualSnippet $visualSnippet)
    {
        return view('visual-snippets::admin.edit', [
            'snippet' => $visualSnippet
        ]);
    }

    /**
     * Atualiza um molde existente.
     */
    public function update(Request $request, VisualSnippet $visualSnippet)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'slug'          => 'required|string|alpha_dash|unique:visual_snippets,slug,' . $visualSnippet->id,
            'description'   => 'nullable|string|max:500',
            'content'       => 'required|string',
            'content_json'  => 'nullable|string',
            'payload'       => 'nullable|string',
            'thumbnail_url' => 'nullable|string',
            'is_active'     => 'boolean',
        ]);

        $validated['content_json'] = !empty($validated['content_json'])
            ? json_decode($validated['content_json'], true)
            : null;

        $validated['payload'] = !empty($validated['payload'])
            ? (json_decode($validated['payload'], true) ?? [])
            : [];

        $validated['is_active'] = $request->boolean('is_active', true);

        if (!empty($validated['thumbnail_url']) && str_starts_with($validated['thumbnail_url'], 'data:image')) {
            // Se já existia uma imagem anterior, apaga antes de salvar a nova
            if (!empty($visualSnippet->thumbnail_url) && function_exists('deleteImage')) {
                $oldPath = parse_url($visualSnippet->thumbnail_url, PHP_URL_PATH);
                $relativeOldPath = ltrim(preg_replace('#^/?storage/#', '', $oldPath), '/');
                deleteImage($relativeOldPath, 'snippets');
            }

            $validated['thumbnail_url'] = saveBase64Image($validated['thumbnail_url'], 'snippets');
        } else {
            $validated['thumbnail_url'] = $visualSnippet->thumbnail_url;
        }

        $visualSnippet->update($validated);

        return redirect()
            ->route('admin.visual-snippets.index')
            ->with('success', 'Snippet visual atualizado com sucesso!');
    }

    /**
     * Exibe uma pré-visualização isolada do snippet.
     */
    public function show(VisualSnippet $visualSnippet)
    {
        return view('visual-snippets::admin.show', [
            'snippet' => $visualSnippet
        ]);
    }

    /**
     * Remove o molde do sistema.
     */
    public function destroy(VisualSnippet $visualSnippet)
    {
        // Limpa a imagem física e suas variações em cache
        if (!empty($visualSnippet->thumbnail_url) && function_exists('deleteImage')) {
            // Garante o caminho relativo limpo caso thumbnail_url venha com URL cheia ou '/storage/'
            $path = parse_url($visualSnippet->thumbnail_url, PHP_URL_PATH);
            $relativePath = ltrim(preg_replace('#^/?storage/#', '', $path), '/');

            deleteImage($relativePath, 'snippets');
        }

        $visualSnippet->delete();

        return redirect()
            ->route('admin.visual-snippets.index')
            ->with('success', 'Snippet visual excluído com sucesso.');
    }
}
