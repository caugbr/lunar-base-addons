@extends('admin.layout')

@section('header_title', 'Tracker: Eventos de Conversão')
@section('header_subtitle', "Relatório de ações e cliques rastreados (Últimos {$days} dias)")

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-mouse-pointer-click class="lucid-icon" /> Eventos & Ações Rastradas</h2>
        <a href="{{ route('admin.tracker.index') }}" class="admin-btn admin-btn-secondary">
            <x-lucide-arrow-left class="lucid-icon" /> <span>Voltar ao Dashboard</span>
        </a>
    </div>

    <form method="GET" action="{{ route('admin.tracker.events') }}" class="admin-filters">
        <div class="admin-filters-row">
            <div class="admin-filter-group" style="flex: 2;">
                <input type="text" name="search" value="{{ $search }}" class="admin-filter-input" placeholder="Buscar por nome do evento...">
            </div>
            <div class="admin-filter-group">
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span style="font-size: 0.9rem; color: #64748b;">Últimos</span>
                    <input type="number" name="days" value="{{ $days }}" min="1" max="365" class="admin-filter-input" style="width: 80px;">
                    <span style="font-size: 0.9rem; color: #64748b;">dias</span>
                </div>
            </div>
            <div class="admin-filter-actions">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <x-lucide-filter class="lucid-icon" /> Filtrar
                </button>
                <a href="{{ route('admin.tracker.events') }}" class="admin-btn admin-btn-secondary">
                    <x-lucide-brush-cleaning class="lucid-icon" /> Limpar
                </a>
            </div>
        </div>
    </form>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Nome do Evento</th>
                    <th>Categoria</th>
                    <th style="text-align: right; width: 160px;">Total de Cliques</th>
                    <th style="text-align: right; width: 160px;">Usuários Únicos</th>
                </tr>
            </thead>
            <tbody>
                @forelse($events as $index => $item)
                <tr>
                    <td style="color: #94a3b8; font-weight: 500;">
                        {{ $events->firstItem() + $index }}
                    </td>
                    <td>
                        <strong style="color: #1e293b; font-size: 0.95rem;">{{ $item->event_name }}</strong>
                    </td>
                    <td>
                        @if($item->event_category)
                            <span class="badge" style="background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">
                                {{ $item->event_category }}
                            </span>
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: 600; color: #2563eb;">
                        {{ number_format($item->total, 0, ',', '.') }}
                    </td>
                    <td style="text-align: right; font-weight: 600; color: #1e293b;">
                        {{ number_format($item->unique_users, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="admin-empty-list">
                            <div>
                                <x-lucide-circle-off class="lucid-icon" />
                            </div>
                            <h3>Nenhum evento registrado no período</h3>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-pagination">
        {{ $events->links() }}
    </div>
</div>
@endsection
