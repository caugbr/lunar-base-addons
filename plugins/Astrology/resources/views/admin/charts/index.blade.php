@extends('admin.layout')
@section('header_title', 'Mapas Astrais')
@section('header_subtitle', 'Gerencie mapas astrais calculados e armazenados')

@once
@push('styles')
<link rel="stylesheet" href="{{ asset('plugins/astrology/css/astrology-admin.css') }}">
@endpush
@endonce

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-compass class="lucid-icon" /> Mapas Cadastrados ({{ $charts->total() }})</h2>
        <a href="{{ route('admin.astrology.charts.create') }}" class="admin-btn admin-btn-primary">
            <x-lucide-circle-plus class="lucid-icon" /> <span>Novo Mapa</span>
        </a>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('admin.astrology.charts.index') }}" class="admin-filters">
        <div class="admin-filters-row">
            <div class="admin-filter-group">
                <input type="text" name="search" value="{{ request('search') }}" class="admin-filter-input" placeholder="Buscar por nome, cidade ou slug...">
            </div>
            <div class="admin-filter-group">
                <select name="sun_sign" class="admin-filter-select">
                    <option value="">Signo Solar</option>
                    @foreach(['Aries', 'Taurus', 'Gemini', 'Cancer', 'Leo', 'Virgo', 'Libra', 'Scorpio', 'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces'] as $sign)
                        <option value="{{ $sign }}" {{ request('sun_sign') === $sign ? 'selected' : '' }}>{{ astro_pt($sign) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="admin-filter-group">
                <select name="is_public" class="admin-filter-select">
                    <option value="">Visibilidade</option>
                    <option value="1" {{ request('is_public') === '1' ? 'selected' : '' }}>Público</option>
                    <option value="0" {{ request('is_public') === '0' ? 'selected' : '' }}>Privado</option>
                </select>
            </div>
            <div class="admin-filter-actions">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <x-lucide-filter class="lucid-icon" /> Filtrar
                </button>
                <a href="{{ route('admin.astrology.charts.index') }}" class="admin-btn admin-btn-secondary">
                    <x-lucide-brush-cleaning class="lucid-icon" /> Limpar
                </a>
            </div>
        </div>
    </form>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nome / Slug</th>
                    <th>Nascimento</th>
                    <th>Local</th>
                    <th>Big 3 (Sol / Lua / Asc)</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($charts as $chart)
                <tr>
                    <td>
                        <strong>{{ $chart->name }}</strong><br>
                        <code>{{ $chart->slug }}</code>
                    </td>
                    <td>
                        {{ $chart->formatted_date_time }}
                    </td>
                    <td>
                        {{ $chart->city ?? 'Lat/Lon direto' }}
                    </td>
                    <td>
                        <div class="astrology-summary-badges">
                            <span class="astrology-badge-sign badge-sun" title="Sol em {{ $chart->sun_sign }}">
                                ☉ {{ astro_pt($chart->sun_sign) ?? '—' }}
                            </span>
                            <span class="astrology-badge-sign badge-moon" title="Lua em {{ $chart->moon_sign }}">
                                ☽ {{ astro_pt($chart->moon_sign) ?? '—' }}
                            </span>
                            <span class="astrology-badge-sign badge-asc" title="Ascendente em {{ $chart->ascendant_sign }}">
                                AC: {{ astro_pt($chart->ascendant_sign) ?? '—' }}
                            </span>
                        </div>
                    </td>
                    <td>
                        @if($chart->is_public)
                            <span class="admin-badge admin-badge-active">Público</span>
                        @else
                            <span class="admin-badge admin-badge-inactive">Privado</span>
                        @endif
                    </td>
                    <td class="admin-actions">
                        <div>
                            <a href="{{ route('admin.astrology.charts.show', $chart->id) }}" class="admin-btn admin-btn-secondary" title="Ver detalhes do mapa">
                                <x-lucide-eye class="lucid-icon" />
                            </a>
                            <a href="{{ route('admin.astrology.charts.edit', $chart->id) }}" class="admin-btn admin-btn-secondary" title="Editar">
                                <x-lucide-pencil class="lucid-icon" />
                            </a>
                            <form method="POST" action="{{ route('admin.astrology.charts.destroy', $chart->id) }}" data-confirm="Remover este mapa astral do banco de dados?" class="inline-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn-danger" title="Excluir">
                                    <x-lucide-trash-2 class="lucid-icon" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="admin-text-center admin-text-muted empty-table-cell">
                        Nenhum mapa astral cadastrado ainda. <a href="{{ route('admin.astrology.charts.create') }}">Crie o primeiro mapa</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-pagination">
        {{ $charts->appends(request()->query())->links() }}
    </div>
</div>
@endsection
