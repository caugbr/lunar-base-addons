@extends('admin.layout')

@section('header_title', 'Tracker: Acessos por Horário')
@section('header_subtitle', "Distribuição de acessos das 00h às 23h (Últimos {$days} dias)")

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-clock class="lucid-icon" /> Picos de Visitação por Horário</h2>
        <a href="{{ route('admin.tracker.index') }}" class="admin-btn admin-btn-secondary">
            <x-lucide-arrow-left class="lucid-icon" /> <span>Voltar ao Dashboard</span>
        </a>
    </div>

    <!-- Filtros de Período -->
    <form method="GET" action="{{ route('admin.tracker.hourly') }}" class="admin-filters">
        <div class="admin-filters-row">
            <div class="admin-filter-group">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <span>Últimos</span>
                    <input type="number" name="days" value="{{ $days }}" min="1" max="365" class="admin-filter-input">
                    <span>dias</span>
                </div>
                {{-- <select name="days" class="admin-filter-select" onchange="this.form.submit()">
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
            </div>
        </div>
    </form>

    {{-- Cards Resumo do Horário --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px;">
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; text-align: center;">
            <span style="font-size: 0.85rem; color: #64748b; font-weight: 500;">Total de Visitas no Período</span>
            <div style="font-size: 1.8rem; font-weight: bold; color: #1e293b; margin-top: 5px;">
                {{ number_format($totalPeriodViews, 0, ',', '.') }}
            </div>
        </div>
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 15px; border-radius: 8px; text-align: center;">
            <span style="font-size: 0.85rem; color: #2563eb; font-weight: 500;">Horário de Maior Pico</span>
            <div style="font-size: 1.8rem; font-weight: bold; color: #1d4ed8; margin-top: 5px;">
                {{ $peakHour }} ({{ number_format($hourlyData[$peakHour] ?? 0, 0, ',', '.') }} acessos)
            </div>
        </div>
    </div>

    {{-- Gráfico em Linha --}}
    <div style="margin-bottom: 30px;">
        <x-chart
            id="chart-hourly-detail"
            type="line"
            label="Acessos por Horário"
            :labels="array_keys($hourlyData)"
            :data="array_values($hourlyData)"
            :height="320"
        />
    </div>

    {{-- Tabela Detalhada das 24h --}}
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Faixa de Horário</th>
                    <th style="text-align: right;">Total de Acessos</th>
                    <th style="text-align: right;">% do Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hourlyData as $hour => $total)
                @php
                    $percentage = $totalPeriodViews > 0 ? ($total / $totalPeriodViews) * 100 : 0;
                    $isPeak = ($hour === $peakHour && $total > 0);
                @endphp
                <tr style="{{ $isPeak ? 'background-color: #f0fdf4;' : '' }}">
                    <td>
                        <strong>{{ $hour }} até {{ sprintf('%02dh59', (int)$hour) }}</strong>
                        @if($isPeak)
                            <span style="font-size: 0.75rem; background: #22c55e; color: white; padding: 2px 6px; border-radius: 4px; margin-left: 8px;">Pico</span>
                        @endif
                    </td>
                    <td style="text-align: right; font-weight: 600; color: {{ $isPeak ? '#15803d' : '#1e293b' }};">
                        {{ number_format($total, 0, ',', '.') }}
                    </td>
                    <td style="text-align: right; color: #64748b;">
                        {{ number_format($percentage, 1, ',', '.') }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
