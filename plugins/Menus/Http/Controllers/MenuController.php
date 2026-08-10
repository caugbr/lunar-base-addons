<?php

namespace Plugins\Menus\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Menus\Models\Menu;
use Plugins\Menus\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Term;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::orderBy('name')->get();
        return view('menus::admin.index', compact('menus'));
    }

    public function create()
    {
        return view('menus::admin.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:menus,slug|alpha_dash',
            'hook' => 'nullable|string|max:255',
        ]);

        $menu = Menu::create($validated);

        return redirect()->route('admin.menus.edit', $menu->id)
            ->with('success', "Menu '{$menu->name}' criado! Agora adicione os links.");
    }

    public function edit(Menu $menu)
    {
        $pages = Page::published()->orderBy('title')->get();
        $posts = Post::published()->orderBy('title')->get();
        $terms = Term::orderBy('name')->get();

        // Mapeia domínios extras disponíveis para o seletor no construtor
        $extraDomains = function_exists('siteDomains') ? siteDomains() : [];
        $availableDomains = [
            ['key' => 'main', 'label' => 'Domínio Principal (Padrão)']
        ];

        foreach ($extraDomains as $extra) {
            $key = !empty($extra['namespace']) ? $extra['namespace'] : (!empty($extra['domain']) ? $extra['domain'] : null);
            if ($key) {
                $label = !empty($extra['domain']) ? $extra['domain'] : $key;
                if (!empty($extra['namespace'])) {
                    $label .= " ({$extra['namespace']})";
                }
                $availableDomains[] = [
                    'key'   => $key,
                    'label' => $label
                ];
            }
        }

        $itemsJson = json_encode($this->getSerializedTree($menu->rootItems));
        $availableDomainsJson = json_encode($availableDomains);

        return view('menus::admin.edit', compact('menu', 'pages', 'posts', 'terms', 'itemsJson', 'availableDomainsJson'));
    }

    protected function getSerializedTree($items): array
    {
        return $items->map(function ($item) {
            return [
                'label'      => $item->label,
                'type'       => $item->type,
                'url'        => $item->url,
                'model_type' => $item->model_type,
                'model_id'   => $item->model_id,
                'target'     => $item->target,
                'class'      => $item->class,
                'domains'    => $item->domains ?? ['*'],
                'children'   => $this->getSerializedTree($item->children),
            ];
        })->toArray();
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => "required|string|max:255|alpha_dash|unique:menus,slug,{$menu->id}",
            'hook' => "nullable|string|max:255",
        ]);

        $menu->update($validated);

        return redirect()->back()
            ->with('success', 'Propriedades do menu atualizadas com sucesso.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();
        return redirect()->route('admin.menus.index')
            ->with('success', 'Menu removido com sucesso.');
    }

    public function saveItems(Request $request, Menu $menu)
    {
        $request->validate([
            'items_json' => 'required|json'
        ]);

        $tree = json_decode($request->items_json, true) ?? [];

        try {
            DB::transaction(function () use ($menu, $tree) {
                $menu->items()->delete();
                $this->saveMenuItemBranch($menu->id, $tree);
            });

            return response()->json([
                'success' => true,
                'message' => 'Estrutura e hierarquia do menu salvas com sucesso!'
            ]);

        } catch (\Exception $e) {
            \Log::error("Erro ao salvar estrutura de menu: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao salvar. Tente novamente.'
            ], 500);
        }
    }

    protected function saveMenuItemBranch(int $menuId, array $branch, ?int $parentId = null): void
    {
        foreach ($branch as $index => $itemData) {

            $item = MenuItem::create([
                'menu_id'    => $menuId,
                'parent_id'  => $parentId,
                'label'      => $itemData['label'] ?? null,
                'type'       => $itemData['type'] ?? 'custom',
                'url'        => $itemData['url'] ?? null,
                'model_type' => $itemData['model_type'] ?? null,
                'model_id'   => $itemData['model_id'] ?? null,
                'order'      => $index,
                'target'     => $itemData['target'] ?? '_self',
                'class'      => $itemData['class'] ?? null,
                'domains'    => $itemData['domains'] ?? ['*'],
            ]);

            if (!empty($itemData['children']) && is_array($itemData['children'])) {
                $this->saveMenuItemBranch($menuId, $itemData['children'], $item->id);
            }
        }
    }
}
