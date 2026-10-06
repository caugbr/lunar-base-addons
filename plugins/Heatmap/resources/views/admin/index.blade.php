@extends('admin.layout')

@section('header_title', 'Mapas de calor')
@section('header_subtitle', 'Analise o comportamento dos visitantes nas suas páginas')

@section('content')
@if(!$tables_available)
    <div class="admin-alert admin-alert-warning heatmap-migrate-notice">
        <x-lucide-database-zap class="lucid-icon" />
        <div class="alert-content">
            <strong>Tabelas do banco de dados pendentes</strong>
            <p>Este plugin requer tabelas adicionais para armazenar os dados de calor e rolagem. Execute a migration pelo terminal:</p>
            <div class="code-copy-box">
                <code>php artisan migrate --path=plugins/Heatmap/database/migrations</code>
            </div>
        </div>
    </div>
    <style>
        .heatmap-migrate-notice {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1rem 1.25rem;
        }
        .heatmap-migrate-notice .alert-content {
            flex: 1;
        }
        .heatmap-migrate-notice strong {
            display: block;
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }
        .heatmap-migrate-notice p {
            margin: 0 0 0.5rem 0;
            font-size: 0.85rem;
            line-height: 1.4;
        }
        .code-copy-box {
            background: rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 6px;
            padding: 6px 12px;
            display: inline-block;
            margin-top: 4px;
        }
        .code-copy-box code {
            font-family: monospace;
            font-size: 0.85rem;
            color: var(--color-text, #0f172a);
            user-select: all; /* Facilita a cópia com um clique */
        }
    </style>
@else
    <div class="admin-card">
        <div class="admin-card-header">
            <h2><x-lucide-flame class="lucid-icon" /> Páginas rastreadas</h2>
            <div class="header-actions">
                <form method="GET" action="{{ route('admin.heatmap.index') }}" class="admin-inline-form">
                    <select name="days" onchange="this.form.submit()" class="admin-filter-select">
                        <option value="7" {{ $days == 7 ? 'selected' : '' }}>Últimos 7 dias</option>
                        <option value="30" {{ $days == 30 ? 'selected' : '' }}>Últimos 30 dias</option>
                        <option value="90" {{ $days == 90 ? 'selected' : '' }}>Últimos 90 dias</option>
                        <option value="0" {{ $days == 0 ? 'selected' : '' }}>Todo o período</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Caminho da página</th>
                        <th>Total de cliques</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                    <tr>
                        <td>
                            <div class="page-path-cell">
                                {{ $page->url_path }}
                            </div>
                        </td>
                        <td>
                            <span class="admin-badge admin-badge-active">
                                {{ number_format($page->total_clicks, 0, ',', '.') }} interações
                            </span>
                        </td>
                        <td class="admin-actions" style="max-width: 200px">
                            <div>
                                <a href="{{ route('admin.heatmap.show', ['path' => $page->url_path, 'days' => $days]) }}" class="admin-btn admin-btn-sm admin-btn-secondary" title="Ver mapa">
                                    <x-lucide-eye class="lucid-icon" />
                                </a>
                                <a href="{{ url($page->url_path) }}" target="_blank" class="admin-btn admin-btn-sm admin-btn-secondary" title="Abrir página pública">
                                    <x-lucide-external-link class="lucid-icon" />
                                </a>
                                <form method="POST" action="{{ route('admin.heatmap.clear') }}" class="admin-inline-form" data-confirm="Zerar o mapa de calor desta página?" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="path" value="{{ $page->url_path }}">
                                    <button type="submit" class="admin-btn admin-btn-sm admin-btn-danger" title="Limpar dados desta página">
                                        <x-lucide-trash-2 class="lucid-icon" />
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">
                            <div class="admin-empty-list">
                                <div>
                                    <x-lucide-flame-kindling class="lucid-icon" />
                                </div>
                                <h3>Nenhum clique registrado ainda</h3>
                                <p>Os cliques dos visitantes começarão a aparecer aqui automaticamente conforme o site for navegado.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">
            {{ $pages->appends(['days' => $days])->links() }}
        </div>
    </div>
@endif
@endsection

@push('styles')
<style>
    .header-actions select {
        -webkit-appearance: none; /* Chrome, Safari, Edge, Opera */
        -moz-appearance: none;    /* Firefox */
        appearance: none;         /* Padrão W3C moderno */
        background-image: none;   /* Garante que não haja seta SVG injetada por frameworks */
    }

    /* Arranca a setinha teimosa no Edge/IE */
    .header-actions select::-ms-expand {
        display: none;
    }
    .page-path-cell {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .device-buttons {
        display: flex;
        gap: 6px;
    }
    .text-muted { color: var(--color-text-muted); }
</style>
@endpush
