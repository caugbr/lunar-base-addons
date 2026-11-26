@extends('admin.layout')
@section('header_title', 'Sobreposição e Variações de Mapas')
@section('header_subtitle', 'Sinastria entre mapas salvos, trânsitos em tempo real e Revolução Solar')

@once
@push('styles')
<link rel="stylesheet" href="{{ asset('plugins/astrology/css/astrology-admin.css') }}">
@endpush
@endonce

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-compass class="lucid-icon" /> Análise combinada e projeções</h2>
        <div class="top-buttons">
            @if($compiledSvg)
                <a href="{{ route('admin.astrology.biwheel.download-svg', request()->query()) }}"
                   class="admin-btn admin-btn-secondary">
                    <x-lucide-download class="lucid-icon" /> <span>Baixar SVG</span>
                </a>
            @endif
            @if($innerChart)
                <a href="{{ route('admin.astrology.charts.show', $innerChart->id) }}" class="admin-btn admin-btn-secondary">
                    <x-lucide-eye class="lucid-icon" /> <span>Ver mapa base</span>
                </a>
            @endif
            <a href="{{ route('admin.astrology.charts.index') }}" class="admin-btn admin-btn-secondary">
                <x-lucide-arrow-left class="lucid-icon" /> <span>Mapas cadastrados</span>
            </a>
        </div>
    </div>

    @if($charts->isEmpty())
        <p class="admin-text-muted">Nenhum mapa astral calculado disponível.</p>
    @else
        <form method="GET" action="{{ route('admin.astrology.biwheel.index') }}" class="admin-filters" id="biwheel_form" data-ignore-preserve>
            <input type="hidden" name="invert" id="invert_input" value="{{ $invert ? '1' : '0' }}">

            <div class="admin-form-row">
                <div class="form-group admin-form-col-1">
                    <label class="field-label" for="inner_id">Mapa pessoal base</label>
                    <select name="inner_id" id="inner_id" class="form-input">
                        @foreach($charts as $c)
                            <option value="{{ $c->id }}" {{ ($innerChart?->id === $c->id) ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->formatted_date_time }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group admin-form-col-1">
                    <label class="field-label" for="outer_type">Tipo de variação / comparação</label>
                    <select name="outer_type" id="outer_type" class="form-input" onchange="toggleOuterMode(this.value)">
                        <option value="chart" {{ $outerType === 'chart' ? 'selected' : '' }}>Outro mapa cadastrado (Sinastria)</option>
                        <option value="transit" {{ $outerType === 'transit' ? 'selected' : '' }}>Trânsitos (Data e hora efêmeras)</option>
                        <option value="solar_return" {{ $outerType === 'solar_return' ? 'selected' : '' }}>Revolução Solar (Anual)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="field-label" for="theme">Tema visual</label>
                    <select name="theme" id="theme" class="form-input">
                        @foreach($availableThemes as $tSlug => $tData)
                            <option value="{{ $tSlug }}" {{ $tSlug === $themeSlug ? 'selected' : '' }}>
                                {{ $tData['name'] ?? ucfirst($tSlug) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Painel 1: Outro mapa cadastrado --}}
            <div id="section_chart" class="admin-form-row" style="{{ $outerType === 'chart' ? '' : 'display:none;' }}">
                <div class="form-group admin-form-col-2">
                    <label class="field-label" for="outer_id">Segundo mapa (coroa externa)</label>
                    <select name="outer_id" id="outer_id" class="form-input">
                        @foreach($charts as $c)
                            <option value="{{ $c->id }}" {{ ($outerChart?->id === $c->id) ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->formatted_date_time }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Painel 2: Transitos (efemero) --}}
            <div id="section_transit" style="{{ $outerType === 'transit' ? '' : 'display:none;' }}">
                <div class="admin-form-row">
                    <div class="form-group">
                        <label class="field-label" for="transit_date">Data do trânsito</label>
                        <x-roller-picker
                            type="date"
                            id="transit_date"
                            name="transit_date"
                            class="astro-calc-input"
                            modal-title="Data do trânsito"
                            :value="$transitDate"
                            breakpoint="768"
                        />
                    </div>
                    <div class="form-group">
                        <label class="field-label" for="transit_time">Hora</label>
                        <x-roller-picker
                            type="time"
                            id="transit_time"
                            name="transit_time"
                            class="astro-calc-input"
                            modal-title="Hora"
                            :value="substr($transitTime, 0, 5)"
                            breakpoint="768"
                        />
                    </div>
                    <div class="form-group">
                        <label class="field-label" for="transit_timezone">Fuso horário</label>
                        <input type="text" name="transit_timezone" id="transit_timezone" class="form-input" value="{{ $transitTimezone }}">
                    </div>
                    <div class="form-group" style="display:flex; align-items:flex-end; padding-bottom: 6px;">
                        <button type="button" class="admin-btn admin-btn-secondary" onclick="fillTransitNow()">
                            <x-lucide-clock class="lucid-icon" /> Momento atual
                        </button>
                    </div>
                </div>
                <div class="admin-form-row">
                    <div class="form-group admin-form-col-1">
                        <label class="field-label" for="transit_city">Local de referência</label>
                        <input type="text" name="transit_city" id="transit_city" class="form-input" value="{{ $transitCity }}">
                    </div>
                    <div class="form-group">
                        <label class="field-label" for="transit_latitude">Latitude</label>
                        <input type="number" step="0.0000001" name="transit_latitude" id="transit_latitude" class="form-input" value="{{ $transitLatitude }}">
                    </div>
                    <div class="form-group">
                        <label class="field-label" for="transit_longitude">Longitude</label>
                        <input type="number" step="0.0000001" name="transit_longitude" id="transit_longitude" class="form-input" value="{{ $transitLongitude }}">
                    </div>
                </div>
            </div>

            {{-- Painel 3: Revolucao Solar (efemera) --}}
            <div id="section_solar_return" style="{{ $outerType === 'solar_return' ? '' : 'display:none;' }}">
                <div class="admin-form-row">
                    <div class="form-group">
                        <label class="field-label" for="sr_year">Ano da Revolução Solar</label>
                        <input type="number" min="1900" max="2150" name="sr_year" id="sr_year" class="form-input" value="{{ $srYear }}">
                    </div>
                    <div class="form-group">
                        <label class="field-label" for="sr_house_system">Sistema de casas da RS</label>
                        <select name="sr_house_system" id="sr_house_system" class="form-input">
                            <option value="placidus" {{ $srHouseSystem === 'placidus' ? 'selected' : '' }}>Placidus</option>
                            <option value="koch" {{ $srHouseSystem === 'koch' ? 'selected' : '' }}>Koch</option>
                            <option value="whole_sign" {{ $srHouseSystem === 'whole_sign' ? 'selected' : '' }}>Signos Inteiros</option>
                            <option value="equal" {{ $srHouseSystem === 'equal' ? 'selected' : '' }}>Casas Iguais</option>
                        </select>
                    </div>
                    @if($innerChart && $outerType === 'solar_return')
                    <div class="form-group" style="display:flex; align-items:flex-end; padding-bottom: 6px;">
                        <a href="{{ route('admin.astrology.charts.solar-return', [
                                $innerChart->id,
                                'sr_year' => $srYear,
                                'sr_city' => $srCity,
                                'sr_latitude' => $srLatitude,
                                'sr_longitude' => $srLongitude,
                                'sr_timezone' => $srTimezone,
                                'sr_house_system' => $srHouseSystem,
                            ]) }}"
                        class="admin-btn admin-btn-secondary">
                            <x-lucide-sun class="lucid-icon" /> <span>Ver revolução solar isolada</span>
                        </a>
                    </div>
                    @endif
                </div>
                <div class="admin-form-row">
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
                </div>
            </div>

            {{-- Parametros de orbes e acoes --}}
            <div class="admin-form-row">
                <div class="form-group">
                    <label class="field-label" for="orb_general">Orbe geral (°)</label>
                    <input type="number" step="0.5" min="0.5" max="12" name="orb_general" id="orb_general" class="form-input" value="{{ $orbGeneral }}">
                </div>

                <div class="form-group">
                    <label class="field-label" for="orb_luminaries">Orbe luminares (°)</label>
                    <input type="number" step="0.5" min="0.5" max="15" name="orb_luminaries" id="orb_luminaries" class="form-input" value="{{ $orbLuminaries }}">
                </div>

                <div class="form-group">
                    <label class="field-label">Aspectos menores</label>
                    <select name="include_minors" class="form-input">
                        <option value="0" {{ !$includeMinors ? 'selected' : '' }}>Não</option>
                        <option value="1" {{ $includeMinors ? 'selected' : '' }}>Sim</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="field-label">Ângulos externos (AC/MC)</label>
                    <select name="include_outer_angles" class="form-input">
                        <option value="1" {{ $includeOuterAngles ? 'selected' : '' }}>Incluir</option>
                        <option value="0" {{ !$includeOuterAngles ? 'selected' : '' }}>Ocultar</option>
                    </select>
                </div>

                <div class="form-group" style="display:flex; align-items:flex-end; gap:8px; padding-bottom: 6px;">
                    <button type="submit" class="admin-btn admin-btn-primary">
                        <x-lucide-refresh-cw class="lucid-icon" /> Calcular e exibir
                    </button>

                    <button type="button"
                            id="btn_invert_rings"
                            class="admin-btn admin-btn-secondary"
                            style="{{ $isSingleView ? 'display:none;' : '' }}"
                            onclick="invertRingsAndSubmit()"
                            title="Trocar quem define as casas (anel interno) e quem vai para a coroa externa">
                        <x-lucide-repeat class="lucid-icon" /> Inverter anéis
                    </button>
                </div>
            </div>
        </form>

        @if($innerChart && !$isSingleView)
            @php
                $outerLabel = match ($outerType) {
                    'transit' => "Trânsitos ({$transitDate} {$transitTime})",
                    'solar_return' => "Revolução Solar {$srYear}",
                    default => $outerChart?->name ?? 'Segundo mapa',
                };
                $innerLabel = $innerChart->name;
                $activeInner = $invert ? $outerLabel : $innerLabel;
                $activeOuter = $invert ? $innerLabel : $outerLabel;
            @endphp
            <div style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--color-text-muted, #94a3b8);">
                <strong>Anel interno (casas):</strong> {{ $activeInner }}
                &nbsp;|&nbsp;
                <strong>Coroa externa:</strong> {{ $activeOuter }}
            </div>
        @endif

        @if($calculationError)
            <div class="admin-card" style="border-color: #ef4444; margin-top: 1rem; padding: 12px;">
                <strong style="color: #ef4444;">Erro ao calcular variação:</strong> {{ $calculationError }}
            </div>
        @endif

        @if(!empty($solarReturnMeta))
            <div class="astrology-summary-grid" style="margin-top: 1rem;">
                <div class="astrology-summary-card">
                    <div class="astrology-card-title">Instante exato da Revolução Solar</div>
                    <div class="astrology-card-value">
                        {{ \Carbon\Carbon::parse($solarReturnMeta['local_datetime'])->format('d/m/Y H:i:s') }}
                    </div>
                    <small class="astrology-card-meta">
                        UTC: {{ $solarReturnMeta['utc_datetime'] }} | Iterações: {{ $solarReturnMeta['iterations'] ?? '—' }}
                    </small>
                </div>
                <div class="astrology-summary-card">
                    <div class="astrology-card-title">Precisão solar</div>
                    <div class="astrology-card-value">{{ number_format($solarReturnMeta['actual_sun_longitude'] ?? 0, 6) }}°</div>
                    <small class="astrology-card-meta">Alvo natal: {{ number_format($solarReturnMeta['target_sun_longitude'] ?? 0, 6) }}°</small>
                </div>
            </div>
        @endif

        @if($compiledSvg)
            <div class="mandala-preview-container" style="margin-top: 1.5rem;">
                <div class="mandala-svg-box" style="max-width: 720px;">
                    {!! $compiledSvg !!}
                </div>
            </div>
        @endif
    @endif
</div>
@endsection

@push('scripts')
<script>
    function toggleOuterMode(mode) {
        document.getElementById('section_chart').style.display = (mode === 'chart') ? '' : 'none';
        document.getElementById('section_transit').style.display = (mode === 'transit') ? '' : 'none';
        document.getElementById('section_solar_return').style.display = (mode === 'solar_return') ? '' : 'none';

        document.getElementById('invert_input').value = (mode === 'solar_return') ? '1' : '0';
        updateInvertButtonVisibility();
    }

    function updateInvertButtonVisibility() {
        const mode = document.getElementById('outer_type').value;
        const srDisplay = document.getElementById('sr_display')?.value ?? 'biwheel';
        const btn = document.getElementById('btn_invert_rings');
        if (!btn) return;

        btn.style.display = (mode === 'solar_return' && srDisplay === 'single') ? 'none' : '';
    }

    function invertRingsAndSubmit() {
        const mode = document.getElementById('outer_type').value;

        if (mode === 'chart') {
            const innerSelect = document.getElementById('inner_id');
            const outerSelect = document.getElementById('outer_id');
            const tmp = innerSelect.value;
            innerSelect.value = outerSelect.value;
            outerSelect.value = tmp;
            document.getElementById('invert_input').value = '0';
        } else {
            const invertInput = document.getElementById('invert_input');
            invertInput.value = (invertInput.value === '1') ? '0' : '1';
        }

        document.getElementById('biwheel_form').submit();
    }

    function fillTransitNow() {
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        const hh = String(now.getHours()).padStart(2, '0');
        const min = String(now.getMinutes()).padStart(2, '0');

        document.getElementById('transit_date').value = `${yyyy}-${mm}-${dd}`;
        document.getElementById('transit_time').value = `${hh}:${min}`;
        document.getElementById('biwheel_form').submit();
    }

    document.getElementById('sr_display')?.addEventListener('change', updateInvertButtonVisibility);
</script>
@endpush
