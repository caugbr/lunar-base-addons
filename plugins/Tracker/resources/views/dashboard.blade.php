@extends('admin.layout')

@section('header_title', 'Tracking de acessos ao Site')
@section('header_subtitle', "Métricas de tráfego e visualizações (Últimos {$days} dias)")

@section('content')
{{-- Cards de Destaque --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 25px;">
    <div class="admin-card" style="text-align: center; padding: 20px;">
        <h4 style="color: #64748b; margin: 0 0 5px; font-size: 0.95rem;">Visualizações Totais</h4>
        <span style="font-size: 2.2rem; font-weight: bold; color: #1e293b;">
            {{ number_format($totalViews, 0, ',', '.') }}
        </span>
        <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 4px;">Últimos {{ $days }} dias</div>
    </div>
    <div class="admin-card" style="text-align: center; padding: 20px;">
        <h4 style="color: #64748b; margin: 0 0 5px; font-size: 0.95rem;">Visitantes Únicos</h4>
        <span style="font-size: 2.2rem; font-weight: bold; color: #2563eb;">
            {{ number_format($uniqueVisitors, 0, ',', '.') }}
        </span>
        <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 4px;">Últimos {{ $days }} dias</div>
    </div>
</div>

{{-- Área de Gráficos 1: Visualizações Diárias + Dispositivos --}}
<div class="graphics-main">
    {{-- Gráfico: Visualizações Diárias --}}
    <div class="admin-card" style="padding: 20px;">
        <div class="card-box-header">
            <div>
                <h3 class="card-box-title">Visualizações Diárias</h3>
                <span class="card-box-subtitle">Últimos {{ $days }} dias</span>
            </div>
        </div>
        <x-chart
            id="chart-daily-views"
            type="bar"
            label="Pageviews"
            :labels="array_keys($dailyViews->toArray())"
            :data="array_values($dailyViews->toArray())"
            :height="280"
        />
    </div>

    {{-- Gráfico: Dispositivos --}}
    <div class="admin-card" style="padding: 20px;">
        <div class="card-box-header">
            <div>
                <h3 class="card-box-title">Dispositivos</h3>
                <span class="card-box-subtitle">Proporção de acesso</span>
            </div>
        </div>
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

{{-- Área de Gráfico 2: Picos de Acesso por Horário (Largura Total) --}}
<div class="admin-card" style="padding: 20px; margin-bottom: 25px;">
    <div class="card-box-header">
        <div>
            <h3 class="card-box-title">Picos de Acesso por Horário</h3>
            <span class="card-box-subtitle">Distribuição das 00h às 23h (Acumulado de {{ $days }} dias)</span>
        </div>
        <a href="{{ route('admin.tracker.hourly') }}" class="box-link">
            Ver detalhes <x-lucide-arrow-right class="lucid-icon" style="width: 14px; height: 14px;" />
        </a>
    </div>
    <x-chart
        id="chart-hourly-views"
        type="line"
        label="Acessos por Horário"
        :labels="array_keys($hourlyViews)"
        :data="array_values($hourlyViews)"
        :height="240"
    />
</div>

{{-- Tabelas de Detalhes --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px;">
    {{-- Top Páginas --}}
    <div class="admin-card" style="padding: 20px;">
        <div class="card-box-header">
            <div>
                <h3 class="card-box-title">Páginas Mais Visitadas</h3>
                <span class="card-box-subtitle">Top {{ $topPagesLimit }} mais acessadas</span>
            </div>
            <a href="{{ route('admin.tracker.pages') }}" class="box-link">
                Ver todas <x-lucide-arrow-right class="lucid-icon" style="width: 14px; height: 14px;" />
            </a>
        </div>
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

    {{-- Top Origens --}}
    <div class="admin-card" style="padding: 20px;">
        <div class="card-box-header">
            <div>
                <h3 class="card-box-title">Origens de Tráfego</h3>
                <span class="card-box-subtitle">Top {{ $topReferrersLimit }} fontes</span>
            </div>
            <a href="{{ route('admin.tracker.referrers') }}" class="box-link">
                Ver todas <x-lucide-arrow-right class="lucid-icon" style="width: 14px; height: 14px;" />
            </a>
        </div>
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

{{-- Abaixo das tabelas de Top Páginas e Origens --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-top: 20px;">
    {{-- Top Páginas --}}
    {{-- Top Origens --}}

    {{-- NOVO BOX: Metas e Eventos de Conversão --}}
    <div class="admin-card" style="padding: 20px;">
        <div class="card-box-header">
            <div>
                <h3 class="card-box-title">Eventos & Conversões</h3>
                <span class="card-box-subtitle">Top {{ $topEventsLimit }} ações mais clicadas</span>
            </div>
            <a href="{{ route('admin.tracker.events') }}" class="box-link">
                Ver todos <x-lucide-arrow-right class="lucid-icon" style="width: 14px; height: 14px;" />
            </a>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Evento / Ação</th>
                    <th style="text-align: right; width: 90px;">Total</th>
                    <th style="text-align: right; width: 90px;">Únicos</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topEvents as $event)
                    <tr>
                        <td>
                            <strong style="color: #1e293b;">{{ $event->event_name }}</strong>
                        </td>
                        <td style="text-align: right; font-weight: 600; color: #2563eb;">
                            {{ number_format($event->total, 0, ',', '.') }}
                        </td>
                        <td style="text-align: right; font-weight: 600; color: #64748b;">
                            {{ number_format($event->unique_users, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" style="text-align: center; color: #94a3b8; padding: 15px;">
                            Nenhum evento registrado ainda.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<style>
    .graphics-main {
        margin-bottom: 25px;
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }

    @media (min-width: 1058px) {
        .graphics-main {
            grid-template-columns: 2fr 1fr;
        }
    }

    .card-box-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }

    .card-box-title {
        font-size: 1.05rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }

    .card-box-subtitle {
        font-size: 0.8rem;
        color: #64748b;
        display: block;
        margin-top: 2px;
    }

    .box-link {
        font-size: 0.85rem;
        color: #2563eb;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 500;
    }

    .box-link:hover {
        text-decoration: underline;
    }

    canvas#chart-daily-views,
    canvas#chart-hourly-views {
        width: 100% !important;
    }

    canvas#chart-devices {
        /* max-width: 260px;
        max-height: 260px; */
        margin: auto;
    }
</style>
@endsection
