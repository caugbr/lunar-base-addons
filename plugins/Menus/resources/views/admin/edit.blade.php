@extends('admin.layout')
@section('header_title', 'Estrutura do Menu')
@section('header_subtitle', 'Editando links de: ' . $menu->name)

@once
@push('styles')
<link rel="stylesheet" href="{{ asset('plugins/menus/css/menus.css') }}">
@endpush
@endonce

@section('content')
<div class="admin-card" style="margin-bottom: 1.5rem;">
    <div class="admin-card-header">
        <h2><x-lucide-square-pen class="lucid-icon" /> Propriedades do Menu</h2>
    </div>
    <form method="POST" action="{{ route('admin.menus.update', $menu->id) }}">
        @csrf
        @method('PUT')

        <div class="admin-form-row" style="align-items: flex-start;">
            <div class="form-group" style="flex: 2;">
                <label for="menu_name">Nome do Menu *</label>
                <input type="text" name="name" id="menu_name" value="{{ old('name', $menu->name) }}" required class="form-input">
            </div>

            <div class="form-group" style="flex: 2;">
                <label for="menu_slug">Slug (URL) *</label>
                <input type="text" name="slug" id="menu_slug" value="{{ old('slug', $menu->slug) }}" required class="form-input">
            </div>

            <div class="form-group" style="flex: 3;">
                <label for="menu_hook">Ponto de Exibição no Tema (Hook opcional)</label>
                {!! render_hooks_select([
                    'name'        => 'hook',
                    'id'          => 'menu_hook',
                    'selected'    => old('hook', $menu->hook),
                    'placeholder' => '-- Nenhum (Apenas chamada manual ou Helper) --'
                ]) !!}
            </div>

            <div class="form-group" style="align-self: flex-end; flex: 0;">
                <button type="submit" class="admin-btn admin-btn-primary" style="white-space: nowrap; height: 38px;">
                    <x-lucide-save class="lucid-icon" /> Atualizar Dados
                </button>
            </div>
        </div>
    </form>
</div>

<div class="menu-builder-container" x-data="menuBuilder({{ $itemsJson }}, {{ $availableDomainsJson }})" x-cloak>

    {{-- Coluna Esquerda --}}
    <div class="sources-column">
        <div class="edit-box">
            <header>Adicionar Itens ao Menu</header>
            <article>

                @foreach(\App\Support\PublicationTypes::all() as $typeKey => $type)
                    @php
                        $modelClass = $type['model'];
                        $items = ($modelClass && class_exists($modelClass)) 
                            ? $modelClass::query()->get() 
                            : [];
                    @endphp
                    <x-menus::source 
                        :title="$type['label']" 
                        :type="$typeKey" 
                        :model="$modelClass" 
                        :items="$items" 
                        :titleField="method_exists($modelClass, 'getTitleAttribute') ? 'title' : 'name'"
                    />
                @endforeach

                <x-menus::source
                    title="Taxonomias"
                    type="term"
                    model="App\Models\Term"
                    :items="$terms"
                    titleField="name"
                    badgeField="taxonomy.name"
                />

                <x-hook name="admin.menus.add_sources" desc="Injeta sanfonas de fontes de links no construtor de menus" />

                <div class="accordion-item" x-data="{ open: false, url: '', label: '' }">
                    <button type="button" class="accordion-header" @click="open = !open">
                        <span>Links Personalizados</span>
                        <x-lucide-chevron-down class="lucid-icon" x-show="!open" />
                        <x-lucide-chevron-up class="lucid-icon" x-show="open" />
                    </button>
                    <div class="accordion-body" x-show="open" style="padding: 12px;">
                        <div class="form-group" style="margin-bottom: 10px;">
                            <label style="font-size: 0.8rem;">URL</label>
                            <input type="text" x-model="url" placeholder="https://exemplo.com" class="form-input">
                        </div>
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label style="font-size: 0.8rem;">Rótulo do Link</label>
                            <input type="text" x-model="label" placeholder="Texto visível" class="form-input">
                        </div>
                        <button type="button"
                                @click="addCustomLink(url, label); url = ''; label = ''"
                                :disabled="!url.trim() || !label.trim()"
                                class="admin-btn admin-btn-secondary btn-sm" style="width: 100%;">
                            Adicionar ao Menu
                        </button>
                    </div>
                </div>

            </article>
        </div>
    </div>

    {{-- Coluna Direita (Árvore) --}}
    <div class="builder-column">
        <div class="admin-card" style="margin-bottom: 0;">
            <div class="admin-card-header">
                <h2><x-lucide-menu class="lucid-icon" /> Itens do menu {{ old('name', $menu->name) }}</h2>
                <a href="{{ route('admin.menus.index') }}" class="admin-btn admin-btn-secondary">
                    <x-lucide-arrow-left class="lucid-icon" /> Voltar
                </a>
            </div>

            <p style="font-size: 0.875rem; color: var(--color-text-muted); margin-bottom: 1.5rem;">
                Adicione links a partir da coluna esquerda, configure os domínios de exibição e use as setas para organizar a hierarquia.
            </p>

            <div class="menu-items-tree">
                <template x-if="flatItems.length === 0">
                    <p style="text-align: center; color: var(--color-text-dim); padding: 3rem 0;">
                        Este menu está vazio. Adicione links para começar.
                    </p>
                </template>

                <div class="tree-list">
                    <template x-for="(item, index) in flatItems" :key="index">
                        <div class="tree-item-wrapper" :style="`padding-left: ${item.depth * 2.5}rem;`" :class="{ 'has-depth': item.depth > 0 }">
                            <div class="tree-item">
                                <div class="tree-item-title">
                                    <span class="item-type-badge" x-text="item.type.toUpperCase()"></span>
                                    <strong x-text="item.label"></strong>
                                    <template x-if="item.type === 'custom'">
                                        <small x-text="item.url" class="admin-text-muted" style="margin-left: 8px; font-weight: normal;"></small>
                                    </template>

                                    <!-- Badge de Domínios -->
                                    <span style="font-size: 0.7rem; padding: 2px 6px; border-radius: 4px; background: #e0e7ff; color: #3730a3; margin-left: 8px;"
                                          x-text="item.domains.includes('*') ? 'Todos os domínios' : item.domains.join(', ')">
                                    </span>
                                </div>

                                <div class="tree-item-controls">
                                    <button type="button" @click="moveUp(index)" :disabled="index === 0" title="Subir">
                                        <x-lucide-chevron-up class="lucid-icon" />
                                    </button>
                                    <button type="button" @click="moveDown(index)" :disabled="index === flatItems.length - 1" title="Descer">
                                        <x-lucide-chevron-down class="lucid-icon" />
                                    </button>
                                    <button type="button" @click="indent(index)" :disabled="index === 0 || flatItems[index].depth > flatItems[index-1].depth" title="Aninhar">
                                        <x-lucide-chevron-right class="lucid-icon" />
                                    </button>
                                    <button type="button" @click="outdent(index)" :disabled="item.depth === 0" title="Recuar">
                                        <x-lucide-chevron-left class="lucid-icon" />
                                    </button>
                                    <button type="button" @click="toggleEdit(index)" class="control-btn-edit" title="Configurações extras">
                                        <x-lucide-settings class="lucid-icon" />
                                    </button>
                                    <button type="button" @click="removeItem(index)" class="control-btn-delete" title="Remover">
                                        <x-lucide-x class="lucid-icon" />
                                    </button>
                                </div>
                            </div>

                            {{-- Configurações do Item --}}
                            <div class="tree-item-settings" x-show="item.editing" x-transition>
                                <div class="admin-form-row">
                                    <div class="form-group" style="flex: 2;">
                                        <label style="font-size: 0.75rem;">Rótulo de exibição</label>
                                        <input type="text" x-model="item.label" class="form-input">
                                    </div>
                                    <div class="form-group" style="flex: 2;">
                                        <label style="font-size: 0.75rem;">Classe CSS extra (opcional)</label>
                                        <input type="text" x-model="item.class" placeholder="ex: btn-destaque" class="form-input">
                                    </div>
                                    <div class="form-group" style="flex: 1;">
                                        <label style="font-size: 0.75rem;">Destino do Link</label>
                                        <select x-model="item.target" class="form-input">
                                            <option value="_self">Mesma guia</option>
                                            <option value="_blank">Nova guia (_blank)</option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Seletor de Visibilidade por Domínio --}}
                                <div class="form-group" style="margin-top: 12px; padding-top: 10px; border-top: 1px dashed var(--color-border, #e5e7eb);">
                                    <label style="font-size: 0.75rem; font-weight: 600; margin-bottom: 6px; display: block;">
                                        Exibir nos Domínios / Contextos:
                                    </label>
                                    <div style="display: flex; gap: 1.25rem; flex-wrap: wrap;">
                                        <label style="font-size: 0.8rem; display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                            <input type="checkbox"
                                                   value="*"
                                                   :checked="item.domains.includes('*')"
                                                   @change="toggleDomain(index, '*')">
                                            <span><strong>Todos os domínios (*)</strong></span>
                                        </label>

                                        <template x-for="domainOpt in availableDomains" :key="domainOpt.key">
                                            <label style="font-size: 0.8rem; display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                                <input type="checkbox"
                                                    :value="domainOpt.key"
                                                    :checked="item.domains.includes(domainOpt.key) && !item.domains.includes('*')"
                                                    @change="toggleDomain(index, domainOpt.key)">
                                                <span x-text="domainOpt.label"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="buttons" style="margin-top: 2rem;">
                <button type="button" @click="saveMenuStructure()" :disabled="saving" class="admin-btn admin-btn-primary">
                    <template x-if="saving">
                        <x-lucide-loader class="lucid-icon animate-spin" />
                    </template>
                    <template x-if="!saving">
                        <x-lucide-save class="lucid-icon" />
                    </template>
                    <span x-text="saving ? 'Salvando...' : 'Salvar Estrutura'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    function menuBuilder(initialItems, availableDomains) {
        return {
            flatItems: [],
            availableDomains: availableDomains || [],
            saving: false,

            init() {
                this.flatItems = this.flattenTree(initialItems);
            },

            flattenTree(tree, depth = 0) {
                let flat = [];
                tree.forEach(item => {
                    flat.push({
                        label: item.label,
                        type: item.type,
                        url: item.url || '',
                        model_type: item.model_type || null,
                        model_id: item.model_id || null,
                        target: item.target || '_self',
                        class: item.class || '',
                        domains: (item.domains && item.domains.length) ? item.domains : ['*'],
                        depth: depth,
                        editing: false
                    });
                    if (item.children && item.children.length > 0) {
                        flat = flat.concat(this.flattenTree(item.children, depth + 1));
                    }
                });
                return flat;
            },

            unflattenTree() {
                let tree = [];
                let stack = [{ depth: -1, children: tree }];

                this.flatItems.forEach(item => {
                    let node = {
                        label: item.label,
                        type: item.type,
                        url: item.url,
                        model_type: item.model_type,
                        model_id: item.model_id,
                        target: item.target,
                        class: item.class,
                        domains: (item.domains && item.domains.length) ? item.domains : ['*'],
                        children: []
                    };

                    while (stack.length > 0 && stack[stack.length - 1].depth >= item.depth) {
                        stack.pop();
                    }

                    if (stack.length > 0) {
                        stack[stack.length - 1].children.push(node);
                    }
                    stack.push({ depth: item.depth, children: node.children });
                });

                return tree;
            },

            toggleDomain(index, domainKey) {
                let current = [...(this.flatItems[index].domains || ['*'])];

                if (domainKey === '*') {
                    if (current.includes('*')) {
                        this.flatItems[index].domains = ['main'];
                    } else {
                        this.flatItems[index].domains = ['*'];
                    }
                    return;
                }

                current = current.filter(d => d !== '*');

                if (current.includes(domainKey)) {
                    current = current.filter(d => d !== domainKey);
                } else {
                    current.push(domainKey);
                }

                if (current.length === 0) {
                    current = ['*'];
                }

                this.flatItems[index].domains = current;
            },

            addCustomLink(url, label) {
                this.flatItems.push({
                    label: label,
                    type: 'custom',
                    url: url,
                    model_type: null,
                    model_id: null,
                    target: '_self',
                    class: '',
                    domains: ['*'],
                    depth: 0,
                    editing: false
                });
            },

            addSelectedToMenu(btnEl) {
                const container = btnEl.closest('.accordion-body');
                const checkboxes = container.querySelectorAll('input[type="checkbox"]:checked');

                checkboxes.forEach(cb => {
                    this.flatItems.push({
                        label: cb.dataset.title,
                        type: cb.dataset.type,
                        url: '',
                        model_type: cb.dataset.model,
                        model_id: parseInt(cb.value),
                        target: '_self',
                        class: '',
                        domains: ['*'],
                        depth: 0,
                        editing: false
                    });
                    cb.checked = false;
                });
            },

            removeItem(index) {
                this.flatItems.splice(index, 1);
            },

            toggleEdit(index) {
                this.flatItems[index].editing = !this.flatItems[index].editing;
            },

            moveUp(index) {
                if (index > 0) {
                    let temp = this.flatItems[index];
                    this.flatItems[index] = this.flatItems[index - 1];
                    this.flatItems[index - 1] = temp;
                }
            },

            moveDown(index) {
                if (index < this.flatItems.length - 1) {
                    let temp = this.flatItems[index];
                    this.flatItems[index] = this.flatItems[index + 1];
                    this.flatItems[index + 1] = temp;
                }
            },

            indent(index) {
                if (index > 0) {
                    const prevItem = this.flatItems[index - 1];
                    if (this.flatItems[index].depth <= prevItem.depth) {
                        this.flatItems[index].depth++;
                    }
                }
            },

            outdent(index) {
                if (this.flatItems[index].depth > 0) {
                    this.flatItems[index].depth--;
                }
            },

            async saveMenuStructure() {
                this.saving = true;
                const tree = this.unflattenTree();

                try {
                    const response = await fetch("{{ route('admin.menus.save_items', $menu->id) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            items_json: JSON.stringify(tree)
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        Dialog.alert(data.message);
                    } else {
                        Dialog.alert(data.message || 'Erro ao salvar o menu.');
                    }

                } catch (e) {
                    console.error('Erro ao salvar menu:', e);
                    Dialog.alert('Erro de conexão com o servidor.');
                } finally {
                    this.saving = false;
                }
            }
        }
    }
</script>
@endpush
