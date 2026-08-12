@props([
    'title',            // Rótulo da sanfona (ex: "Produtos")
    'type',             // Tipo polimórfico (ex: "produto")
    'model',            // Namespace da Model (ex: "Plugins\Produtos\Models\Produto")
    'items'      => [], // Coleção Eloquent ou Array de itens
    'titleField' => 'title', // Campo do título (padrão: 'title', fallback: 'name')
    'badgeField' => null,    // Campo opcional para badge (ex: 'namespace' ou 'taxonomy')
])

@if(!empty($items) && (is_countable($items) ? count($items) > 0 : true))
<div class="accordion-item" x-data="{ open: false }">
    <button type="button" class="accordion-header" @click="open = !open">
        <span>{{ $title }}</span>
        <x-lucide-chevron-down class="lucid-icon" x-show="!open" />
        <x-lucide-chevron-up class="lucid-icon" x-show="open" />
    </button>
    <div class="accordion-body" x-show="open">
        <div class="checkbox-list">
            @foreach($items as $item)
                @php
                    $itemTitle = object_get($item, $titleField, object_get($item, 'name', 'Item'));
                    $badge = $badgeField ? object_get($item, $badgeField) : null;
                    $displayTitle = $itemTitle . ($badge ? " [{$badge}]" : '');
                    $itemId = object_get($item, 'id');
                @endphp
                <label class="checkbox-label">
                    <input type="checkbox"
                           value="{{ $itemId }}"
                           data-title="{{ $itemTitle }}"
                           data-type="{{ $type }}"
                           data-model="{{ $model }}">
                    <span>{{ $displayTitle }}</span>
                </label>
            @endforeach
        </div>
        <div style="text-align: center; padding-top: 1rem;">
            <button type="button" @click="addSelectedToMenu($el)" class="admin-btn admin-btn-secondary btn-sm">
                Adicionar ao Menu
            </button>
        </div>
    </div>
</div>
@endif
