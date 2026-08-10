@foreach($items as $item)
    @php
        // Filtra visibilidade por domínio no item atual
        if (!$item->isVisibleForCurrentSite()) {
            continue;
        }

        // Filtra os filhos visíveis no contexto atual
        $visibleChildren = $item->children->filter(fn($child) => $child->isVisibleForCurrentSite());
        $hasChildren = $visibleChildren->isNotEmpty();

        $activeClass = request()->url() === $item->url ? 'active' : '';
    @endphp

    <li class="menu-item {{ $hasChildren ? 'has-children' : '' }} {{ $item->class }} {{ $activeClass }}">
        <a href="{{ $item->url }}" target="{{ $item->target }}" class="menu-link">
            {{ $item->label }}
        </a>

        @if($hasChildren)
            <ul class="sub-menu">
                @include('menus::public.menu', ['items' => $visibleChildren])
            </ul>
        @endif
    </li>
@endforeach
