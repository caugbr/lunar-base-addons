@extends('admin.layout')

@section('header_title', 'Tracker: Origens de Tráfego')
@section('header_subtitle', "Relatório detalhado de referrers e fontes de tráfego (Últimos {$days} dias)")

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-globe class="lucid-icon" /> Origens de Tráfego (Referrers)</h2>
        <a href="{{ route('admin.tracker.index') }}" class="admin-btn admin-btn-secondary">
            <x-lucide-arrow-left class="lucid-icon" /> <span>Voltar ao Dashboard</span>
        </a>
    </div>

    <!-- Filtros -->
    <form method="GET" action="{{ route('admin.tracker.referrers') }}" class="admin-filters">
        <div class="admin-filters-row">
            <div class="admin-filter-group" style="flex: 2;">
                <input type="text" name="search" value="{{ $search }}" class="admin-filter-input" placeholder="Buscar por domínio ou origem (ex: google.com, instagram)...">
            </div>
            <div class="admin-filter-group">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <span>Últimos</span>
                    <input type="number" name="days" value="{{ $days }}" min="1" max="365" class="admin-filter-input">
                    <span>dias</span>
                </div>
                {{-- <select name="days" class="admin-filter-select">
                    <option value="7" {{ $days == 7 ? 'selected' : '' }}>Últimos 7 dias</option>
                    <option value="15" {{ $days == 15 ? 'selected' : '' }}>Últimos 15 dias</option>
                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>Últimos 30 dias</option>
                    <option value="60" {{ $days == 60 ? 'selected' : '' }}>Últimos 60 dias</option>
                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>Últimos 90 dias</option>
                </select> --}}
            </div>
            <div class="admin-filter-actions">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <x-lucide-filter class="lucid-icon" /> Filtrar
                </button>
                <a href="{{ route('admin.tracker.referrers') }}" class="admin-btn admin-btn-secondary">
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
                    <th>Domínio / Fonte de Origem</th>
                    <th style="text-align: right; width: 160px;">Total de Cliques</th>
                    <th style="text-align: right; width: 160px;">Visitantes Únicos</th>
                    <th style="text-align: center; width: 80px;">Link</th>
                </tr>
            </thead>
            <tbody>
                @forelse($referrers as $index => $ref)
                <tr>
                    <td style="color: #94a3b8; font-weight: 500;">
                        {{ $referrers->firstItem() + $index }}
                    </td>
                    <td>
                        <span style="font-weight: 600; color: #1e293b;">{{ $ref->referrer_host }}</span>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: #2563eb;">
                        {{ number_format($ref->total, 0, ',', '.') }}
                    </td>
                    <td style="text-align: right; font-weight: 600; color: #1e293b;">
                        {{ number_format($ref->unique_visitors, 0, ',', '.') }}
                    </td>
                    <td style="text-align: center;">
                        <a href="https://{{ $ref->referrer_host }}" class="admin-btn admin-btn-secondary" target="_blank" rel="noopener noreferrer" title="Visitar domínio">
                            <x-lucide-external-link class="lucid-icon" />
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="admin-empty-list">
                            <div>
                                <x-lucide-circle-off class="lucid-icon" />
                            </div>
                            <h3>Nenhuma origem externa registrada ainda</h3>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-pagination">
        {{ $referrers->links() }}
    </div>
</div>
@endsection
