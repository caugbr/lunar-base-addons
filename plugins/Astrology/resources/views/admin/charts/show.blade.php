@extends('admin.layout')

@php
    $isSolarReturn = $isSolarReturn ?? false;
    $displayChart = $displayChart ?? $chart;
    $availableThemes = \Plugins\Astrology\Services\ThemeManager::getThemes();
    $currentThemeSlug = request('theme', $themeSlug ?? 'classic');
@endphp

@section('header_title', $isSolarReturn ? 'Revolução Solar ' . ($srYear ?? '') : 'Detalhes do Mapa Astral')
@section('header_subtitle', $displayChart->name . ' — ' . $displayChart->formatted_date_time)

@once
@push('styles')
@if($isSolarReturn)
<meta name="admin-ignore-filter-preserve" content="1">
@endif
<link rel="stylesheet" href="{{ asset('plugins/astrology/css/astrology-admin.css') }}">
@endpush
@endonce

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-compass class="lucid-icon" /> {{ $displayChart->name }}</h2>
        <div class="top-buttons">
            @if($isSolarReturn)
                <a href="{{ route('admin.astrology.charts.show', $chart->id) }}"
                   class="admin-btn admin-btn-secondary">
                    <x-lucide-arrow-left class="lucid-icon" /> <span>Mapa Natal</span>
                </a>

                <a href="{{ route('admin.astrology.biwheel.index', [
                        'inner_id'        => $chart->id,
                        'outer_type'      => 'solar_return',
                        'sr_year'         => $srYear,
                        'sr_city'         => $srCity,
                        'sr_latitude'     => $srLatitude,
                        'sr_longitude'    => $srLongitude,
                        'sr_timezone'     => $srTimezone,
                        'sr_house_system' => $srHouseSystem,
                    ]) }}"
                   class="admin-btn admin-btn-primary">
                    <x-lucide-layers class="lucid-icon" /> <span>Revolução + Natal</span>
                </a>

                <a href="{{ route('admin.astrology.charts.solar-return.download-svg', [$chart->id] + request()->query()) }}"
                   class="admin-btn admin-btn-secondary"
                   title="Baixar SVG da Revolução Solar">
                    <x-lucide-download class="lucid-icon" /> <span>Baixar SVG</span>
                </a>
            @else
                <a href="{{ route('admin.astrology.biwheel.index', ['inner_id' => $chart->id, 'outer_type' => 'transit']) }}"
                   class="admin-btn admin-btn-secondary"
                   title="Ver trânsitos do momento atual sobre este mapa">
                    <x-lucide-clock class="lucid-icon" /> <span>Trânsitos</span>
                </a>

                <a href="{{ route('admin.astrology.charts.solar-return', $chart->id) }}"
                   class="admin-btn admin-btn-secondary"
                   title="Ver Revolução Solar isolada com casas e tabelas">
                    <x-lucide-sun class="lucid-icon" /> <span>Revolução solar</span>
                </a>

                <a href="{{ route('admin.astrology.biwheel.index', ['inner_id' => $chart->id, 'outer_type' => 'solar_return']) }}"
                   class="admin-btn admin-btn-secondary"
                   title="Sobrepor Revolução Solar ao Mapa Natal">
                    <x-lucide-layers class="lucid-icon" /> <span>Revolução + Natal</span>
                </a>

                <a href="{{ route('admin.astrology.biwheel.index', ['inner_id' => $chart->id, 'outer_type' => 'chart']) }}"
                   class="admin-btn admin-btn-secondary"
                   title="Comparar este mapa com outro cadastrado">
                    <x-lucide-orbit class="lucid-icon" /> <span>Sinastria</span>
                </a>

                <form method="POST" action="{{ route('admin.astrology.charts.recalculate', $chart->id) }}" class="inline-form">
                    @csrf
                    <button type="submit" class="admin-btn admin-btn-secondary" title="Recalcular dados astronômicos">
                        <x-lucide-refresh-cw class="lucid-icon" /> <span>Recalcular</span>
                    </button>
                </form>

                <a href="{{ route('admin.astrology.charts.download-svg', [$chart->id, 'theme' => $currentThemeSlug]) }}"
                   class="admin-btn admin-btn-secondary"
                   title="Baixar imagem SVG única">
                    <x-lucide-download class="lucid-icon" /> <span>Baixar SVG</span>
                </a>

                <a href="{{ route('admin.astrology.charts.edit', $chart->id) }}" class="admin-btn admin-btn-primary">
                    <x-lucide-pencil class="lucid-icon" /> <span>Editar</span>
                </a>
                <a href="{{ route('admin.astrology.charts.index') }}" class="admin-btn admin-btn-secondary">
                    <x-lucide-arrow-left class="lucid-icon" /> <span>Voltar</span>
                </a>
            @endif
        </div>
    </div>

    {{-- Formulario de ano e relocacao quando em modo Revolucao Solar --}}
    @if($isSolarReturn)
        <form method="GET" action="{{ route('admin.astrology.charts.solar-return', $chart->id) }}" class="admin-filters" style="margin-top: 1rem;" data-ignore-preserve>
            <input type="hidden" name="theme" value="{{ $currentThemeSlug }}">
            <div class="admin-form-row">
                <div class="form-group">
                    <label class="field-label" for="sr_year">Ano da Revolução</label>
                    <input type="number" min="1900" max="2150" name="sr_year" id="sr_year" class="form-input" value="{{ $srYear }}">
                </div>
                <div class="form-group">
                    <label class="field-label" for="sr_house_system">Sistema de casas</label>
                    <select name="sr_house_system" id="sr_house_system" class="form-input">
                        <option value="placidus" {{ $srHouseSystem === 'placidus' ? 'selected' : '' }}>Placidus</option>
                        <option value="koch" {{ $srHouseSystem === 'koch' ? 'selected' : '' }}>Koch</option>
                        <option value="whole_sign" {{ $srHouseSystem === 'whole_sign' ? 'selected' : '' }}>Signos Inteiros</option>
                        <option value="equal" {{ $srHouseSystem === 'equal' ? 'selected' : '' }}>Casas Iguais</option>
                    </select>
                </div>
                <div class="form-group admin-form-col-1">
                    <label class="field-label" for="sr_city">Cidade do aniversário (relocação)</label>
                    <x-cities-autocomplete
                        name="sr_city"
                        id="sr_city"
                        :value="$srCity"
                        placeholder="Buscar cidade..."
                        lat-target="#sr_latitude"
                        lng-target="#sr_longitude"
                        timezone-target="#sr_timezone"
                    />
                    <small>Comece a digitar para ver sugestões</small>
                </div>
            </div>
            <div class="admin-form-row">
                <div class="form-group">
                    <label class="field-label" for="sr_latitude">Latitude</label>
                    <input type="number" step="0.0000001" name="sr_latitude" id="sr_latitude" class="form-input" value="{{ $srLatitude }}">
                </div>
                <div class="form-group">
                    <label class="field-label" for="sr_longitude">Longitude</label>
                    <input type="number" step="0.0000001" name="sr_longitude" id="sr_longitude" class="form-input" value="{{ $srLongitude }}">
                </div>
                <div class="form-group">
                    <label class="field-label" for="sr_timezone">Fuso horário</label>
                    <input type="text" name="sr_timezone" id="sr_timezone" class="form-input" value="{{ $srTimezone }}">
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <x-lucide-refresh-cw class="lucid-icon" /> Atualizar Revolução
                    </button>
                </div>
            </div>
        </form>

        @if(!empty($calculationError))
            <div class="admin-card" style="border-color: #ef4444; margin-top: 1rem; padding: 12px;">
                <strong style="color: #ef4444;">Erro ao calcular Revolução Solar:</strong> {{ $calculationError }}
            </div>
        @endif
    @endif

    {{-- Cards de Resumo Rápido --}}
    <div class="astrology-summary-grid">
        <div class="astrology-summary-card">
            <div class="astrology-card-title">{{ $isSolarReturn ? 'Instante exato do retorno' : 'Nascimento' }}</div>
            <div class="astrology-card-value">
                @if($isSolarReturn && !empty($solarReturnMeta['local_datetime']))
                    {{ \Carbon\Carbon::parse($solarReturnMeta['local_datetime'])->format('d/m/Y H:i:s') }}
                @else
                    {{ $displayChart->formatted_date_time }}
                @endif
            </div>
            <small class="astrology-card-meta">
                {{ $displayChart->timezone }}
                @if($isSolarReturn && !empty($solarReturnMeta['utc_datetime']))
                    | UTC: {{ $solarReturnMeta['utc_datetime'] }}
                @endif
            </small>
        </div>

        <div class="astrology-summary-card">
            <div class="astrology-card-title">Localização</div>
            <div class="astrology-card-value">{{ $displayChart->city ?? 'Coordenadas' }}</div>
            <small class="astrology-card-meta">{{ $displayChart->latitude }}, {{ $displayChart->longitude }}</small>
        </div>

        <div class="astrology-summary-card">
            <div class="astrology-card-title">Big Three</div>
            <div class="astrology-summary-badges">
                <span class="astrology-badge-sign badge-sun">☉ {{ astro_pt($displayChart->sun_sign) }}</span>
                <span class="astrology-badge-sign badge-moon">☽ {{ astro_pt($displayChart->moon_sign) }}</span>
                <span class="astrology-badge-sign badge-asc">AC: {{ astro_pt($displayChart->ascendant_sign) }}</span>
            </div>
        </div>

        @if($isSolarReturn && !empty($solarReturnMeta))
            <div class="astrology-summary-card">
                <div class="astrology-card-title">Precisão solar</div>
                <div class="astrology-card-value">{{ number_format($solarReturnMeta['actual_sun_longitude'] ?? 0, 6) }}°</div>
                <small class="astrology-card-meta">
                    Alvo natal: {{ number_format($solarReturnMeta['target_sun_longitude'] ?? 0, 6) }}° | {{ $solarReturnMeta['iterations'] ?? '—' }} iterações
                </small>
            </div>
        @else
            <div class="astrology-summary-card">
                <div class="astrology-card-title">Shortcode</div>
                <code class="astrology-shortcode">[natal_chart slug="{{ $chart->slug }}"]</code>
            </div>
        @endif
    </div>

    {{-- SEÇÃO VISUAL: MANDALA DESENHADA + SELETOR DE TEMAS --}}
    <div class="astrology-grid-show">
        <div>
            <div class="astrology-section-header">
                <h3>
                    <x-lucide-circle-star class="lucid-icon" /> Roda do Mapa Astral
                </h3>

                <div class="astrology-theme-picker">
                    <label class="astrology-theme-label">Tema:</label>
                    <select onchange="changeChartTheme(this.value)" class="form-input form-input-sm">
                        @foreach($availableThemes as $tSlug => $tData)
                            <option value="{{ $tSlug }}" {{ $tSlug === $currentThemeSlug ? 'selected' : '' }}>
                                {{ $tData['name'] ?? ucfirst($tSlug) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mandala-preview-container">
                <div id="mandala-viewport" class="mandala-svg-box">
                    {!! $compiledSvg !!}
                </div>
            </div>
        </div>

        {{-- Ângulos e Cúspides ao lado da Mandala --}}
        <div>
            <h3>
                <x-lucide-compass class="lucid-icon" /> Cúspides das 12 Casas
            </h3>
            <div class="table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Casa / Ângulo</th>
                            <th>Posição</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($displayChart->angles as $angName => $ang)
                        <tr>
                            <td><strong class="text-amber">{{ astro_pt($angName) }}</strong></td>
                            <td><x-astrology-position :value="$ang['formatted']" :theme="$currentThemeSlug" /></td>
                        </tr>
                        @endforeach

                        @foreach($displayChart->houses as $num => $h)
                        <tr>
                            <td>Casa {{ $num }}</td>
                            <td><x-astrology-position :value="$h['formatted']" :theme="$currentThemeSlug" /></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- TABELA DE PLANETAS --}}
    <div class="astrology-section-wrapper">
        <h3>
            <x-lucide-orbit class="lucid-icon" /> Posições Planetárias
        </h3>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Corpo Celeste</th>
                        <th>Posição Exata</th>
                        <th>Casa</th>
                        <th>Velocidade Diária</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($displayChart->planets as $name => $p)
                    <tr>
                        <td>
                            <strong>{{ astro_pt($name) }}</strong>
                            @if(!empty($p['is_retrograde']))
                                <span class="badge-r">R</span>
                            @endif
                        </td>
                        <td><x-astrology-position :value="$p['formatted']" :theme="$currentThemeSlug" /></td>
                        <td>
                            @if(isset($p['house']))
                                <span class="badge-house">Casa {{ $p['house'] }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ number_format($p['daily_speed'] ?? 0, 4) }}°/dia</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ASPECTOS PLANETÁRIOS --}}
    @if(!empty($displayChart->aspects))
    <div class="astrology-section-wrapper">
        <h3>
            <x-lucide-grid class="lucid-icon" /> Grade de aspectos
        </h3>
        <x-astrology-aspect-grid :chart="$displayChart" :theme="$currentThemeSlug" />
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function changeChartTheme(theme) {
        const url = new URL(window.location.href);
        url.searchParams.set('theme', theme);
        window.location.href = url.toString();
    }
</script>
@endpush
