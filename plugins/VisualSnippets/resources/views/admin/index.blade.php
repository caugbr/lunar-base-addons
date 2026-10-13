@extends('admin.layout')

@section('header_title', 'Snippets Visuais')
@section('header_subtitle', 'Galeria de moldes e layouts reutilizáveis para o editor')

@section('content')
{{-- 1. CHECAGEM DE TABELA DISPONÍVEL --}}
@if(!dbAvailable('visual_snippets'))
    <div class="admin-card" style="border-left: 4px solid var(--color-warning, #f59e0b);">
        <div style="display: flex; align-items: flex-start; gap: 12px; padding: 1rem;">
            <x-lucide-triangle-alert class="lucid-icon" style="color: var(--color-warning, #f59e0b); flex-shrink: 0; margin-top: 2px;" />
            <div>
                <h3 style="margin: 0 0 6px 0; font-size: 1rem; color: var(--color-text, #1e293b);">
                    Tabela de banco de dados não encontrada
                </h3>
                <p style="margin: 0 0 10px 0; font-size: 0.875rem; color: var(--color-text-muted, #64748b);">
                    O plugin <strong>Visual Snippets</strong> foi ativado, mas as tabelas necessárias ainda não foram migradas.
                </p>
                <p style="margin: 0; font-size: 0.85rem;">
                    Execute o comando no terminal:
                    <code style="background: var(--color-bg-dark, #f1f5f9); padding: 2px 8px; border-radius: 4px; font-weight: 600;">php artisan migrate</code>
                </p>
            </div>
        </div>
    </div>
@else
    {{-- 2. GALERIA VISUAL QUANDO A TABELA EXISTE --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>
                <x-lucide-layout-template class="lucid-icon" />
                <span>Galeria de Snippets Visuais</span>
            </h2>
            <a href="{{ route('admin.visual-snippets.create') }}" class="admin-btn admin-btn-primary">
                <x-lucide-plus class="lucid-icon" />
                <span>Novo Snippet</span>
            </a>
        </div>

        {{-- Filtros de Busca --}}
        <form method="GET" action="{{ route('admin.visual-snippets.index') }}" class="admin-filters">
            <div class="admin-filters-row">
                <div class="admin-filter-group">
                    <input type="text" name="title" value="{{ request('title') }}"
                           class="admin-filter-input" placeholder="Buscar por título...">
                </div>
                <div class="admin-filter-group">
                    <select name="is_active" class="admin-filter-select">
                        <option value="">Todos os status</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Ativos</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inativos</option>
                    </select>
                </div>
                <div class="admin-filter-actions">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <x-lucide-filter class="lucid-icon" />
                        <span>Filtrar</span>
                    </button>
                    <a href="{{ route('admin.visual-snippets.index') }}" class="admin-btn admin-btn-secondary">
                        <x-lucide-brush-cleaning class="lucid-icon" />
                        <span>Limpar</span>
                    </a>
                </div>
            </div>
        </form>

        {{-- GRADE VISUAL (CARDS LADO A LADO) --}}
        <div class="snippet-cards-grid">
            @forelse($snippets as $snippet)
                <div class="snippet-card">
                    {{-- Miniatura do Bloco --}}
                    <div class="snippet-card-thumb">
                        <a href="{{ route('admin.visual-snippets.show', $snippet->id) }}" class="snippet-thumb-link">
                            @if($snippet->thumbnail_url)
                                <img src="{{ $snippet->thumbnail_url }}" alt="{{ $snippet->title }}" loading="lazy">
                            @else
                                <div class="snippet-thumb-placeholder">
                                    <x-lucide-image class="lucid-icon" />
                                    <span>Sem miniatura</span>
                                </div>
                            @endif
                        </a>

                        {{-- Status Badge Flutuante --}}
                        <div class="snippet-status-badge">
                            @if($snippet->is_active)
                                <span class="admin-badge admin-badge-active">Ativo</span>
                            @else
                                <span class="admin-badge admin-badge-inactive">Inativo</span>
                            @endif
                        </div>
                    </div>

                    {{-- Conteúdo do Card --}}
                    <div class="snippet-card-body">
                        <h3 class="snippet-title">
                            <a href="{{ route('admin.visual-snippets.show', $snippet->id) }}">
                                {{ $snippet->title }}
                            </a>
                        </h3>

                        @if($snippet->description)
                            <p class="snippet-desc">{{ Str::limit($snippet->description, 70) }}</p>
                        @endif

                        {{-- Área de Cópia do Shortcode --}}
                        {{-- <div class="snippet-shortcode-wrap"> --}}
                            {{-- <code>[vs-template slug="{{ $snippet->slug }}"]</code>
                            <button type="button"
                                    class="btn-copy-sc"
                                    title="Copiar shortcode"
                                    onclick="navigator.clipboard.writeText('[vs-template slug=&quot;{{ $snippet->slug }}&quot;]'); this.innerText='Copiado!'; setTimeout(() => this.innerText='Copiar', 1500)">
                                Copiar
                            </button> --}}
                            {{-- <x-copy-text :text="'[vs-template slug=&quot;' . $snippet->slug . '&quot;]'" /> --}}
                            <x-copy-text text='[vs-template slug="{{ $snippet->slug }}"]' />
                        {{-- </div> --}}
                    </div>

                    {{-- Rodapé com Ações --}}
                    <div class="snippet-card-footer">
                        <span class="snippet-date">{{ $snippet->created_at->format('d/m/Y') }}</span>

                        <div class="snippet-actions">
                            <a href="{{ route('admin.visual-snippets.show', $snippet->id) }}"
                               class="admin-btn admin-btn-secondary" style="padding: 4px 8px;" title="Visualizar">
                                <x-lucide-eye class="lucid-icon" />
                            </a>
                            <a href="{{ route('admin.visual-snippets.edit', $snippet->id) }}"
                               class="admin-btn admin-btn-secondary" style="padding: 4px 8px;" title="Editar">
                                <x-lucide-pencil class="lucid-icon" />
                            </a>
                            <form method="POST" action="{{ route('admin.visual-snippets.destroy', $snippet->id) }}"
                                  data-confirm="Excluir este snippet visual?" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn-danger" style="padding: 4px 8px;" title="Excluir">
                                    <x-lucide-trash-2 class="lucid-icon" />
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="snippet-empty-state">
                    <x-lucide-layout-template class="lucid-icon" style="width: 48px; height: 48px; margin-bottom: 12px; color: var(--color-text-muted);" />
                    <p style="margin: 0; color: var(--color-text-muted); font-size: 1rem;">Nenhum snippet visual cadastrado ainda.</p>
                    <a href="{{ route('admin.visual-snippets.create') }}" class="admin-btn admin-btn-primary" style="margin-top: 14px;">
                        <x-lucide-plus class="lucid-icon" /> Criar o primeiro
                    </a>
                </div>
            @endforelse
        </div>

        @if(dbAvailable('visual_snippets'))
            <div class="admin-pagination">
                {{ $snippets->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

    <style>
        /* Grade responsiva de Cards */
        .snippet-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1.25rem;
            align-items: start;
        }

        .snippet-card {
            background: var(--color-card-bg, #ffffff);
            border: 1px solid var(--color-border, #e2e8f0);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .snippet-card:hover {
            transform: translateY(-2px);
            border-color: var(--color-primary, #3b82f6);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }

        /* Área da Miniatura */
        .snippet-card-thumb {
            position: relative;
            width: 100%;
            height: auto;
            background: var(--color-bg-dark, #f8fafc);
            border-bottom: 1px solid var(--color-border, #e2e8f0);
            overflow: hidden;
        }

        .snippet-thumb-link {
            display: block;
            width: 100%;
            height: 100%;
        }

        .snippet-card-thumb img {
            width: 100%;
            height: auto;
            object-fit: contain;
            display: block;
            transition: transform 0.3s ease;
        }

        .snippet-card:hover .snippet-card-thumb img {
            transform: scale(1.03);
        }

        .snippet-thumb-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--color-text-muted, #94a3b8);
            font-size: 0.8rem;
            gap: 6px;
        }

        .snippet-status-badge {
            position: absolute;
            top: 8px;
            right: 8px;
        }

        /* Corpo do Card */
        .snippet-card-body {
            padding: 1rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .snippet-title {
            margin: 0 0 6px 0;
            font-size: 1rem;
            font-weight: 600;
        }

        .snippet-title a {
            color: var(--color-text, #1e293b);
            text-decoration: none;
        }

        .snippet-title a:hover {
            color: var(--color-primary, #3b82f6);
        }

        .snippet-desc {
            margin: 0 0 12px 0;
            font-size: 0.825rem;
            color: var(--color-text-muted, #64748b);
            line-height: 1.4;
            flex-grow: 1;
        }

        /* Faixa de Cópia do Shortcode */
        .snippet-shortcode-wrap {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--color-bg-light, #f8fafc);
            border: 1px solid var(--color-border, #e2e8f0);
            border-radius: 6px;
            padding: 4px 8px;
            margin-top: auto;
        }

        .snippet-shortcode-wrap code {
            font-size: 0.75rem;
            color: var(--color-text, #0f172a);
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .btn-copy-sc {
            background: var(--color-card-bg, #ffffff);
            border: 1px solid var(--color-border, #cbd5e1);
            color: var(--color-text, #475569);
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-copy-sc:hover {
            background: var(--color-primary, #3b82f6);
            color: #ffffff;
            border-color: var(--color-primary, #3b82f6);
        }

        /* Rodapé do Card */
        .snippet-card-footer {
            padding: 8px 1rem;
            border-top: 1px solid var(--color-border, #e2e8f0);
            background: var(--color-bg-light, #fafafa);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .snippet-date {
            font-size: 0.75rem;
            color: var(--color-text-muted, #94a3b8);
        }

        .snippet-actions {
            display: flex;
            gap: 4px;
        }

        /* Estado Vazio */
        .snippet-empty-state {
            grid-column: 1 / -1;
            padding: 3rem 1rem;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
    </style>
@endif
@endsection
