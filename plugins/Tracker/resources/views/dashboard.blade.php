@extends('admin.layout')

@section('header_title', 'Tracking de acessos ao Site')
@section('header_subtitle', 'Métricas de tráfego e visualizações (Últimos 30 dias)')

@section('content')
{{-- Cards de Destaque --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 25px;">
    <div class="admin-card" style="text-align: center; padding: 20px;">
        <h4 style="color: #64748b; margin: 0 0 5px; font-size: 0.95rem;">Visualizações Totais</h4>
        <span style="font-size: 2.2rem; font-weight: bold; color: #1e293b;">
            {{ number_format($totalViews, 0, ',', '.') }}
        </span>
    </div>
    <div class="admin-card" style="text-align: center; padding: 20px;">
        <h4 style="color: #64748b; margin: 0 0 5px; font-size: 0.95rem;">Visitantes Únicos</h4>
        <span style="font-size: 2.2rem; font-weight: bold; color: #2563eb;">
            {{ number_format($uniqueVisitors, 0, ',', '.') }}
        </span>
    </div>
</div>

{{-- Área de Gráficos com o Componente --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 25px;">
    {{-- Gráfico 1: Visualizações por Dia (Linha/Barra) --}}
    <div class="admin-card" style="padding: 20px;">
        <h3 style="margin-bottom: 15px; font-size: 1.1rem; color: #1e293b;">Visualizações Diárias</h3>
        <x-chart
            id="chart-daily-views"
            type="bar"
            label="Pageviews"
            :labels="array_keys($dailyViews->toArray())"
            :data="array_values($dailyViews->toArray())"
            :height="280"
        />
    </div>

    {{-- Gráfico 2: Dispositivos (Rosca/Doughnut) --}}
    <div class="admin-card" style="padding: 20px;">
        <h3 style="margin-bottom: 15px; font-size: 1.1rem; color: #1e293b;">Dispositivos</h3>
        <x-chart
            id="chart-devices"
            type="doughnut"
            label="Dispositivos"
            :labels="array_keys($devices->toArray())"
            :data="array_values($devices->toArray())"
            :colors="['#3b82f6', '#10b981', '#f59e0b']"
            :height="280"
        />
    </div>
</div>

{{-- Tabelas de Detalhes --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">
    {{-- Top 10 Páginas Mais Visitadas --}}
    <div class="admin-card" style="padding: 20px;">
        <h3 style="margin-bottom: 15px; font-size: 1.1rem; color: #1e293b;">Páginas Mais Visitadas</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Página / URL</th>
                    <th style="text-align: right; width: 100px;">Visitas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topPages as $page)
                    <tr>
                        <td style="word-break: break-all;">{{ $page->path }}</td>
                        <td style="text-align: right; font-weight: 600; color: #2563eb;">
                            {{ number_format($page->total, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align: center; color: #94a3b8; padding: 15px;">
                            Nenhum dado registrado ainda.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Top Origens de Tráfego --}}
    <div class="admin-card" style="padding: 20px;">
        <h3 style="margin-bottom: 15px; font-size: 1.1rem; color: #1e293b;">Origens de Tráfego (Referrers)</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Origem</th>
                    <th style="text-align: right; width: 100px;">Visitas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topReferrers as $ref)
                    <tr>
                        <td>{{ $ref->referrer_host }}</td>
                        <td style="text-align: right; font-weight: 600; color: #2563eb;">
                            {{ number_format($ref->total, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" style="text-align: center; color: #94a3b8; padding: 15px;">
                            Acessos diretos ou sem referenciador.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<style>
    canvas#chart-daily-views {
        width: 100% !important;
    }
    canvas#chart-devices {
        max-width: 280px;
        max-height: 280px;
        margin: auto;
    }
</style>
@endsection
