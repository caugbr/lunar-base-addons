@php
    $activeMode = old('mode', $inputs['mode'] ?? 'natal');
    $p1Unknown = old('unknown_time', $inputs['unknown_time'] ?? false);
    $p2Unknown = old('p2_unknown_time', $inputs['p2_unknown_time'] ?? false);
    $hasAdvancedErrors = $errors->hasAny(['latitude', 'longitude', 'timezone', 'house_system', 'theme']);
    $hasTransitCoordsErrors = $errors->hasAny(['transit_latitude', 'transit_longitude', 'transit_timezone']);
    $hasSrCoordsErrors = $errors->hasAny(['sr_latitude', 'sr_longitude', 'sr_timezone']);
    $hasP2CoordsErrors = $errors->hasAny(['p2_latitude', 'p2_longitude', 'p2_timezone']);
@endphp

@once
@push('styles')
<link rel="stylesheet" href="{{ asset('plugins/astrology/css/astrology-public.css') }}">
@endpush
@endonce

<div class="astro-calc">
    <header class="astro-calc-header">
        <h1 class="astro-calc-title">Calculadora Astrológica</h1>
        <p class="astro-calc-subtitle">
            Calcule gratuitamente seu Mapa Natal, Trânsitos do momento, Revolução Solar ou Sinastria.
        </p>
    </header>

    @if(session('astrology_error'))
        <div class="astro-calc-alert astro-calc-alert-error" role="alert">
            {{ session('astrology_error') }}
        </div>
    @endif

    @if(session('astrology_info'))
        <div class="astro-calc-alert astro-calc-alert-info" role="status">
            {{ session('astrology_info') }}
        </div>
    @endif

    @if($errors->any())
        <div class="astro-calc-alert astro-calc-alert-error" role="alert">
            <strong>Verifique os campos antes de continuar:</strong>
            <ul>
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('astrology.calculator.calculate') }}" class="astro-calc-card" id="astro_public_form">
        @csrf
        <input type="hidden" name="mode" id="astro_mode_input" value="{{ $activeMode }}">

        {{-- Abas de selecao do tipo de mapa --}}
        <div class="astro-calc-tabs" role="tablist" aria-label="Tipo de cálculo astrológico">
            <button type="button" class="astro-calc-tab {{ $activeMode === 'natal' ? 'is-active' : '' }}" data-mode="natal">
                Mapa Natal
            </button>
            <button type="button" class="astro-calc-tab {{ $activeMode === 'transit' ? 'is-active' : '' }}" data-mode="transit">
                Trânsitos
            </button>
            <button type="button" class="astro-calc-tab {{ $activeMode === 'solar_return' ? 'is-active' : '' }}" data-mode="solar_return">
                Revolução Solar
            </button>
            <button type="button" class="astro-calc-tab {{ $activeMode === 'synastry' ? 'is-active' : '' }}" data-mode="synastry">
                Sinastria
            </button>
        </div>

        {{-- Bloco comum: Pessoa 1 (Dados Natais) --}}
        <section class="astro-calc-section">
            <h2 class="astro-calc-section-title" id="p1_heading">
                {{ $activeMode === 'synastry' ? '1ª Pessoa — Dados de nascimento' : 'Seus dados de nascimento' }}
            </h2>

            <div class="astro-calc-grid astro-calc-grid-3">
                <div class="astro-calc-field">
                    <label for="p1_name">Nome ou apelido <span class="astro-calc-opt">(opcional)</span></label>
                    <input type="text" id="p1_name" name="name" class="astro-calc-input"
                           value="{{ old('name', $inputs['name']) }}" placeholder="Ex: Ana Paula">
                </div>

                <div class="astro-calc-field">
                    <label for="p1_birth_date">Data de nascimento *</label>
                    <input type="date" id="p1_birth_date" name="birth_date" class="astro-calc-input"
                           value="{{ old('birth_date', $inputs['birth_date']) }}" required>
                </div>

                <div class="astro-calc-field">
                    <label for="p1_birth_time">Hora de nascimento</label>
                    <input type="time" id="p1_birth_time" name="birth_time" class="astro-calc-input"
                           value="{{ old('birth_time', $inputs['birth_time']) }}"
                           {{ $p1Unknown ? 'disabled' : '' }}>
                    <label class="astro-calc-checkbox">
                        <input type="checkbox" name="unknown_time" id="p1_unknown_time" value="1" {{ $p1Unknown ? 'checked' : '' }}>
                        <span>Não sei a hora exata (usar 12:00)</span>
                    </label>
                </div>
            </div>

            <div class="astro-calc-grid astro-calc-grid-1">
                <div class="astro-calc-field">
                    <label for="p1_city">Cidade de nascimento *</label>
                    <x-cities-autocomplete
                        name="city"
                        id="p1_city"
                        :value="old('city', $inputs['city'])"
                        placeholder="Digite o nome da cidade e selecione na lista..."
                        lat-target="#p1_latitude"
                        lng-target="#p1_longitude"
                        timezone-target="#p1_timezone"
                    />
                    <small class="astro-calc-hint">Escolha uma cidade na lista para preencher coordenadas e fuso horário automaticamente.</small>
                </div>
            </div>

            <details class="astro-calc-details" {{ $hasAdvancedErrors ? 'open' : '' }}>
                <summary>Opções avançadas e coordenadas manuais</summary>
                <div class="astro-calc-grid astro-calc-grid-3">
                    <div class="astro-calc-field">
                        <label for="p1_latitude">Latitude</label>
                        <input type="number" step="0.0000001" id="p1_latitude" name="latitude" class="astro-calc-input"
                               value="{{ old('latitude', $inputs['latitude']) }}" placeholder="-23.5505">
                    </div>
                    <div class="astro-calc-field">
                        <label for="p1_longitude">Longitude</label>
                        <input type="number" step="0.0000001" id="p1_longitude" name="longitude" class="astro-calc-input"
                               value="{{ old('longitude', $inputs['longitude']) }}" placeholder="-46.6333">
                    </div>
                    <div class="astro-calc-field">
                        <label for="p1_timezone">Fuso horário</label>
                        <input type="text" id="p1_timezone" name="timezone" class="astro-calc-input"
                               value="{{ old('timezone', $inputs['timezone']) }}">
                    </div>
                </div>

                <div class="astro-calc-grid astro-calc-grid-2" style="margin-top: 12px;">
                    <div class="astro-calc-field">
                        <label for="astro_house_system">Sistema de casas</label>
                        @php $curHsys = old('house_system', $inputs['house_system']); @endphp
                        <select id="astro_house_system" name="house_system" class="astro-calc-input">
                            <option value="placidus" {{ $curHsys === 'placidus' ? 'selected' : '' }}>Placidus (Padrão)</option>
                            <option value="koch" {{ $curHsys === 'koch' ? 'selected' : '' }}>Koch</option>
                            <option value="whole_sign" {{ $curHsys === 'whole_sign' ? 'selected' : '' }}>Signos Inteiros (Whole Sign)</option>
                            <option value="equal" {{ $curHsys === 'equal' ? 'selected' : '' }}>Casas Iguais</option>
                        </select>
                    </div>

                    @if(count($availableThemes) > 1)
                        <div class="astro-calc-field">
                            <label for="astro_theme">Estilo visual da mandala</label>
                            @php $curTheme = old('theme', $inputs['theme']); @endphp
                            <select id="astro_theme" name="theme" class="astro-calc-input">
                                @foreach($availableThemes as $tSlug => $tData)
                                    <option value="{{ $tSlug }}" {{ $curTheme === $tSlug ? 'selected' : '' }}>
                                        {{ $tData['name'] ?? ucfirst($tSlug) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="theme" value="{{ old('theme', $inputs['theme']) }}">
                    @endif
                </div>
            </details>
        </section>

        {{-- Painel extra: Transitos --}}
        <section class="astro-calc-section astro-calc-mode-panel" data-panel="transit" style="{{ $activeMode === 'transit' ? '' : 'display:none;' }}">
            <h2 class="astro-calc-section-title">Momento do trânsito</h2>
            <div class="astro-calc-grid astro-calc-grid-3">
                <div class="astro-calc-field">
                    <label for="transit_date">Data</label>
                    <input type="date" id="transit_date" name="transit_date" class="astro-calc-input"
                           value="{{ old('transit_date', $inputs['transit_date']) }}">
                </div>
                <div class="astro-calc-field">
                    <label for="transit_time">Hora</label>
                    <input type="time" id="transit_time" name="transit_time" class="astro-calc-input"
                           value="{{ old('transit_time', substr((string) $inputs['transit_time'], 0, 5)) }}">
                </div>
                <div class="astro-calc-field">
                    <label for="transit_city">Cidade atual <span class="astro-calc-opt">(opcional)</span></label>
                    <x-cities-autocomplete
                        name="transit_city"
                        id="transit_city"
                        :value="old('transit_city', $inputs['transit_city'])"
                        placeholder="Mesma de nascimento se vazio..."
                        lat-target="#transit_latitude"
                        lng-target="#transit_longitude"
                        timezone-target="#transit_timezone"
                    />
                </div>
            </div>

            <details class="astro-calc-details" {{ $hasTransitCoordsErrors ? 'open' : '' }}>
                <summary>Coordenadas do trânsito (opcional)</summary>
                <div class="astro-calc-grid astro-calc-grid-3">
                    <div class="astro-calc-field">
                        <label for="transit_latitude">Latitude</label>
                        <input type="number" step="0.0000001" id="transit_latitude" name="transit_latitude" class="astro-calc-input"
                               value="{{ old('transit_latitude', $inputs['transit_latitude']) }}">
                    </div>
                    <div class="astro-calc-field">
                        <label for="transit_longitude">Longitude</label>
                        <input type="number" step="0.0000001" id="transit_longitude" name="transit_longitude" class="astro-calc-input"
                               value="{{ old('transit_longitude', $inputs['transit_longitude']) }}">
                    </div>
                    <div class="astro-calc-field">
                        <label for="transit_timezone">Fuso horário</label>
                        <input type="text" id="transit_timezone" name="transit_timezone" class="astro-calc-input"
                               value="{{ old('transit_timezone', $inputs['transit_timezone']) }}">
                    </div>
                </div>
            </details>
        </section>

        {{-- Painel extra: Revolucao Solar --}}
        <section class="astro-calc-section astro-calc-mode-panel" data-panel="solar_return" style="{{ $activeMode === 'solar_return' ? '' : 'display:none;' }}">
            <h2 class="astro-calc-section-title">Dados da Revolução Solar</h2>
            <div class="astro-calc-grid astro-calc-grid-3">
                <div class="astro-calc-field">
                    <label for="sr_year">Ano da Revolução Solar *</label>
                    <input type="number" min="1900" max="2150" id="sr_year" name="sr_year" class="astro-calc-input"
                           value="{{ old('sr_year', $inputs['sr_year']) }}">
                </div>
                <div class="astro-calc-field">
                    <label for="sr_display">Visualização</label>
                    @php $curSrDisp = old('sr_display', $inputs['sr_display']); @endphp
                    <select id="sr_display" name="sr_display" class="astro-calc-input">
                        <option value="single" {{ $curSrDisp === 'single' ? 'selected' : '' }}>Só a revolução solar</option>
                        <option value="biwheel" {{ $curSrDisp === 'biwheel' ? 'selected' : '' }}>Revolução + Mapa Natal</option>
                    </select>
                </div>
                <div class="astro-calc-field">
                    <label for="sr_city">Cidade do aniversário <span class="astro-calc-opt">(opcional)</span></label>
                    <x-cities-autocomplete
                        name="sr_city"
                        id="sr_city"
                        :value="old('sr_city', $inputs['sr_city'])"
                        placeholder="Mesma de nascimento se vazio..."
                        lat-target="#sr_latitude"
                        lng-target="#sr_longitude"
                        timezone-target="#sr_timezone"
                    />
                </div>
            </div>

            <details class="astro-calc-details" {{ $hasSrCoordsErrors ? 'open' : '' }}>
                <summary>Coordenadas do aniversário (opcional)</summary>
                <div class="astro-calc-grid astro-calc-grid-3">
                    <div class="astro-calc-field">
                        <label for="sr_latitude">Latitude</label>
                        <input type="number" step="0.0000001" id="sr_latitude" name="sr_latitude" class="astro-calc-input"
                               value="{{ old('sr_latitude', $inputs['sr_latitude']) }}">
                    </div>
                    <div class="astro-calc-field">
                        <label for="sr_longitude">Longitude</label>
                        <input type="number" step="0.0000001" id="sr_longitude" name="sr_longitude" class="astro-calc-input"
                               value="{{ old('sr_longitude', $inputs['sr_longitude']) }}">
                    </div>
                    <div class="astro-calc-field">
                        <label for="sr_timezone">Fuso horário</label>
                        <input type="text" id="sr_timezone" name="sr_timezone" class="astro-calc-input"
                               value="{{ old('sr_timezone', $inputs['sr_timezone']) }}">
                    </div>
                </div>
            </details>
        </section>

        {{-- Painel extra: Sinastria (Pessoa 2) --}}
        <section class="astro-calc-section astro-calc-mode-panel" data-panel="synastry" style="{{ $activeMode === 'synastry' ? '' : 'display:none;' }}">
            <h2 class="astro-calc-section-title">2ª Pessoa — Dados de nascimento</h2>
            <div class="astro-calc-grid astro-calc-grid-3">
                <div class="astro-calc-field">
                    <label for="p2_name">Nome ou apelido <span class="astro-calc-opt">(opcional)</span></label>
                    <input type="text" id="p2_name" name="p2_name" class="astro-calc-input"
                           value="{{ old('p2_name', $inputs['p2_name']) }}" placeholder="Ex: João">
                </div>

                <div class="astro-calc-field">
                    <label for="p2_birth_date">Data de nascimento *</label>
                    <input type="date" id="p2_birth_date" name="p2_birth_date" class="astro-calc-input"
                           value="{{ old('p2_birth_date', $inputs['p2_birth_date']) }}">
                </div>

                <div class="astro-calc-field">
                    <label for="p2_birth_time">Hora de nascimento</label>
                    <input type="time" id="p2_birth_time" name="p2_birth_time" class="astro-calc-input"
                           value="{{ old('p2_birth_time', $inputs['p2_birth_time']) }}"
                           {{ $p2Unknown ? 'disabled' : '' }}>
                    <label class="astro-calc-checkbox">
                        <input type="checkbox" name="p2_unknown_time" id="p2_unknown_time" value="1" {{ $p2Unknown ? 'checked' : '' }}>
                        <span>Não sei a hora exata (usar 12:00)</span>
                    </label>
                </div>
            </div>

            <div class="astro-calc-grid astro-calc-grid-1">
                <div class="astro-calc-field">
                    <label for="p2_city">Cidade de nascimento *</label>
                    <x-cities-autocomplete
                        name="p2_city"
                        id="p2_city"
                        :value="old('p2_city', $inputs['p2_city'])"
                        placeholder="Digite o nome da cidade e selecione na lista..."
                        lat-target="#p2_latitude"
                        lng-target="#p2_longitude"
                        timezone-target="#p2_timezone"
                    />
                </div>
            </div>

            <details class="astro-calc-details" {{ $hasP2CoordsErrors ? 'open' : '' }}>
                <summary>Coordenadas manuais da 2ª pessoa</summary>
                <div class="astro-calc-grid astro-calc-grid-3">
                    <div class="astro-calc-field">
                        <label for="p2_latitude">Latitude</label>
                        <input type="number" step="0.0000001" id="p2_latitude" name="p2_latitude" class="astro-calc-input"
                               value="{{ old('p2_latitude', $inputs['p2_latitude']) }}">
                    </div>
                    <div class="astro-calc-field">
                        <label for="p2_longitude">Longitude</label>
                        <input type="number" step="0.0000001" id="p2_longitude" name="p2_longitude" class="astro-calc-input"
                               value="{{ old('p2_longitude', $inputs['p2_longitude']) }}">
                    </div>
                    <div class="astro-calc-field">
                        <label for="p2_timezone">Fuso horário</label>
                        <input type="text" id="p2_timezone" name="p2_timezone" class="astro-calc-input"
                               value="{{ old('p2_timezone', $inputs['p2_timezone']) }}">
                    </div>
                </div>
            </details>
        </section>

        <div class="astro-calc-actions">
            <button type="submit" class="astro-calc-btn-primary">
                Calcular mapa
            </button>
        </div>
    </form>

    {{-- RESULTADO CALCULADO --}}
    @if($result)
        <section class="astro-calc-result" id="astro_result">
            <div class="astro-calc-result-header">
                <div>
                    <h2 class="astro-calc-result-title">{{ $result['title'] }}</h2>
                    <p class="astro-calc-result-subtitle">{{ $result['subtitle'] }}</p>
                </div>

                <div class="astro-calc-result-actions">
                    @if(($result['mode'] ?? '') === 'solar_return' && $resultToken)
                        @php
                            $isSrBiwheel = ($result['active_sr_view'] ?? 'single') === 'biwheel';
                            $toggleTargetView = $isSrBiwheel ? 'single' : 'biwheel';
                        @endphp
                        <a href="{{ route('astrology.calculator.index', ['r' => $resultToken, 'sr_view' => $toggleTargetView]) }}#astro_result"
                           class="astro-calc-btn-secondary">
                            {{ $isSrBiwheel ? 'Ver apenas Revolução Solar' : 'Sobrepor ao Mapa Natal' }}
                        </a>
                    @endif

                    @if($resultToken)
                        <a href="{{ route('astrology.calculator.download-svg', array_filter(['token' => $resultToken, 'sr_view' => $result['active_sr_view'] ?? null])) }}"
                           class="astro-calc-btn-secondary">
                            Baixar SVG
                        </a>
                    @endif
                </div>
            </div>

            @if(!empty($result['unknown_time']))
                <div class="astro-calc-alert astro-calc-alert-info">
                    Foi utilizado o horário aproximado de <strong>12:00</strong>. O Ascendente e as casas astrológicas dependem da hora exata de nascimento.
                </div>
            @endif

            @if(!empty($result['solar_return_meta']['local_datetime']))
                <div class="astro-calc-meta-pill">
                    Instante exato da Revolução Solar:
                    <strong>{{ \Carbon\Carbon::parse($result['solar_return_meta']['local_datetime'])->format('d/m/Y H:i:s') }}</strong>
                </div>
            @endif

            {{-- Resumo / Legenda antes da Mandala --}}
            @if(($result['view_type'] ?? '') === 'single' && $displayChart)
                <div class="astro-calc-big3">
                    <div class="astro-calc-big3-card">
                        <span class="astro-calc-big3-label">Sol</span>
                        <div class="astro-calc-big3-val">
                            <x-astrology-sign-icon :sign="$displayChart->sun_sign" :theme="$result['theme']" :showName="true" />
                        </div>
                    </div>
                    <div class="astro-calc-big3-card">
                        <span class="astro-calc-big3-label">Lua</span>
                        <div class="astro-calc-big3-val">
                            <x-astrology-sign-icon :sign="$displayChart->moon_sign" :theme="$result['theme']" :showName="true" />
                        </div>
                    </div>
                    <div class="astro-calc-big3-card">
                        <span class="astro-calc-big3-label">Ascendente</span>
                        <div class="astro-calc-big3-val">
                            <x-astrology-sign-icon :sign="$displayChart->ascendant_sign" :theme="$result['theme']" :showName="true" />
                        </div>
                    </div>
                </div>
            @elseif(($result['view_type'] ?? '') === 'biwheel')
                <div class="astro-calc-biwheel-legend">
                    <div class="astro-calc-legend-item">
                        <span class="astro-calc-dot astro-calc-dot-inner"></span>
                        <div>
                            <strong>Anel interno (casas):</strong> {{ $result['inner_label'] }}
                            <div class="astro-calc-legend-sub">{{ $result['inner_subtitle'] }}</div>
                        </div>
                    </div>
                    <div class="astro-calc-legend-item">
                        <span class="astro-calc-dot astro-calc-dot-outer"></span>
                        <div>
                            <strong>Coroa externa:</strong> {{ $result['outer_label'] }}
                            <div class="astro-calc-legend-sub">{{ $result['outer_subtitle'] }}</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- 1º Bloco Visual: Mandala SVG --}}
            <div class="astro-calc-mandala-box">
                {!! $result['svg'] !!}
            </div>

            {{-- Tabelas e Grade de Aspectos abaixo da Mandala --}}
            @if(($result['view_type'] ?? '') === 'single' && $displayChart)
                <div class="astro-calc-tables-grid">
                    <div class="astro-calc-card">
                        <h3 class="astro-calc-table-title">Posições Planetárias</h3>
                        <div class="astro-calc-table-wrap">
                            <table class="astro-calc-table">
                                <thead>
                                    <tr>
                                        <th>Astro</th>
                                        <th>Posição</th>
                                        <th>Casa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($displayChart->planets as $pName => $pData)
                                        <tr>
                                            <td>
                                                <strong>{{ astro_pt($pName) }}</strong>
                                                @if(!empty($pData['is_retrograde']))
                                                    <span class="astro-calc-retro" title="Retrógrado">R</span>
                                                @endif
                                            </td>
                                            <td><x-astrology-position :value="$pData['formatted']" :theme="$result['theme']" /></td>
                                            <td>{{ isset($pData['house']) ? 'Casa ' . $pData['house'] : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="astro-calc-card">
                        <h3 class="astro-calc-table-title">Ângulos e Casas</h3>
                        <div class="astro-calc-table-wrap">
                            <table class="astro-calc-table">
                                <thead>
                                    <tr>
                                        <th>Ponto / Casa</th>
                                        <th>Posição</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($displayChart->angles as $angName => $angData)
                                        <tr>
                                            <td><strong>{{ astro_pt($angName) }}</strong></td>
                                            <td><x-astrology-position :value="$angData['formatted']" :theme="$result['theme']" /></td>
                                        </tr>
                                    @endforeach
                                    @foreach($displayChart->houses as $hNum => $hData)
                                        <tr>
                                            <td>Casa {{ $hNum }}</td>
                                            <td><x-astrology-position :value="$hData['formatted']" :theme="$result['theme']" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                @if(!empty($displayChart->aspects))
                    <div class="astro-calc-card" style="margin-top: 20px;">
                        <h3 class="astro-calc-table-title">Grade de Aspectos</h3>
                        <x-astrology-aspect-grid :chart="$displayChart" :theme="$result['theme']" />
                    </div>
                @endif

            @elseif(($result['view_type'] ?? '') === 'biwheel')
                @if(($result['mode'] ?? '') === 'transit')
                    {{-- Trânsitos: apenas uma tabela (planetas em trânsito nas casas do mapa natal) --}}
                    <div class="astro-calc-card">
                        <h3 class="astro-calc-table-title">
                            {{ $result['outer_label'] }} nas casas de {{ $result['inner_label'] }}
                        </h3>
                        <div class="astro-calc-table-wrap">
                            <table class="astro-calc-table">
                                <thead>
                                    <tr>
                                        <th>Astro</th>
                                        <th>Posição</th>
                                        <th>Casa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(($result['outer_planets'] ?? []) as $pName => $pData)
                                        @php $overlayHouse = $result['house_overlays'][$pName] ?? null; @endphp
                                        <tr>
                                            <td>
                                                <strong>{{ astro_pt($pName) }}</strong>
                                                @if(!empty($pData['is_retrograde']))
                                                    <span class="astro-calc-retro" title="Retrógrado">R</span>
                                                @endif
                                            </td>
                                            <td><x-astrology-position :value="$pData['formatted'] ?? ''" :theme="$result['theme']" /></td>
                                            <td>{{ $overlayHouse ? 'Casa ' . $overlayHouse : '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    {{-- Sinastria e Revolução Solar: duas tabelas (Externo nas casas do Interno + Interno nas casas do Externo) --}}
                    <div class="astro-calc-tables-grid">
                        <div class="astro-calc-card">
                            <h3 class="astro-calc-table-title">
                                {{ $result['outer_label'] }} nas casas de {{ $result['inner_label'] }}
                            </h3>
                            <div class="astro-calc-table-wrap">
                                <table class="astro-calc-table">
                                    <thead>
                                        <tr>
                                            <th>Astro</th>
                                            <th>Posição</th>
                                            <th>Casa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(($result['outer_planets'] ?? []) as $pName => $pData)
                                            @php $overlayHouse = $result['house_overlays'][$pName] ?? null; @endphp
                                            <tr>
                                                <td>
                                                    <strong>{{ astro_pt($pName) }}</strong>
                                                    @if(!empty($pData['is_retrograde']))
                                                        <span class="astro-calc-retro" title="Retrógrado">R</span>
                                                    @endif
                                                </td>
                                                <td><x-astrology-position :value="$pData['formatted'] ?? ''" :theme="$result['theme']" /></td>
                                                <td>{{ $overlayHouse ? 'Casa ' . $overlayHouse : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="astro-calc-card">
                            <h3 class="astro-calc-table-title">
                                {{ $result['inner_label'] }} nas casas de {{ $result['outer_label'] }}
                            </h3>
                            <div class="astro-calc-table-wrap">
                                <table class="astro-calc-table">
                                    <thead>
                                        <tr>
                                            <th>Astro</th>
                                            <th>Posição</th>
                                            <th>Casa</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(($result['inner_planets'] ?? []) as $pName => $pData)
                                            @php $reverseHouse = $result['reverse_house_overlays'][$pName] ?? null; @endphp
                                            <tr>
                                                <td>
                                                    <strong>{{ astro_pt($pName) }}</strong>
                                                    @if(!empty($pData['is_retrograde']))
                                                        <span class="astro-calc-retro" title="Retrógrado">R</span>
                                                    @endif
                                                </td>
                                                <td><x-astrology-position :value="$pData['formatted'] ?? ''" :theme="$result['theme']" /></td>
                                                <td>{{ $reverseHouse ? 'Casa ' . $reverseHouse : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                @if(!empty($biwheelData))
                    <div class="astro-calc-card" style="margin-top: 20px;">
                        <h3 class="astro-calc-table-title">Grade de Aspectos Cruzados</h3>
                        <x-astrology-aspect-grid
                            :biwheel="$biwheelData"
                            :theme="$result['theme']"
                            :include-outer-angles="$result['include_outer_angles'] ?? true"
                        />
                    </div>
                @endif
            @endif
        </section>
    @endif
</div>

@once
@push('scripts')
<script>
(function () {
    const STORAGE_KEY = 'astrology_public_natal_v1';
    const form = document.getElementById('astro_public_form');
    const modeInput = document.getElementById('astro_mode_input');
    const p1Heading = document.getElementById('p1_heading');
    const tabs = document.querySelectorAll('.astro-calc-tab');
    const panels = document.querySelectorAll('.astro-calc-mode-panel');

    function setMode(mode) {
        if (!modeInput) return;
        modeInput.value = mode;

        tabs.forEach(tab => {
            tab.classList.toggle('is-active', tab.dataset.mode === mode);
        });

        panels.forEach(panel => {
            panel.style.display = (panel.dataset.panel === mode) ? '' : 'none';
        });

        if (p1Heading) {
            p1Heading.textContent = (mode === 'synastry')
                ? '1ª Pessoa — Dados de nascimento'
                : 'Seus dados de nascimento';
        }
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            setMode(this.dataset.mode);
        });
    });

    function bindUnknownTime(checkboxId, timeInputId) {
        const cb = document.getElementById(checkboxId);
        const input = document.getElementById(timeInputId);
        if (!cb || !input) return;

        cb.addEventListener('change', function () {
            input.disabled = this.checked;
            if (this.checked && !input.value) {
                input.value = '12:00';
            }
        });
    }

    bindUnknownTime('p1_unknown_time', 'p1_birth_time');
    bindUnknownTime('p2_unknown_time', 'p2_birth_time');

    // Persiste dados natais da pessoa 1 no navegador para facilitar novos calculos
    const savedFields = [
        ['p1_name', 'name'],
        ['p1_birth_date', 'birth_date'],
        ['p1_birth_time', 'birth_time'],
        ['p1_city', 'city'],
        ['p1_latitude', 'latitude'],
        ['p1_longitude', 'longitude'],
        ['p1_timezone', 'timezone']
    ];

    const hasResultOrErrors = {{ ($result || $errors->any()) ? 'true' : 'false' }};
    if (!hasResultOrErrors) {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (raw) {
                const data = JSON.parse(raw);
                savedFields.forEach(([elId, key]) => {
                    const el = document.getElementById(elId);
                    if (el && !el.value && data[key]) {
                        el.value = data[key];
                    }
                });
                const unknownCb = document.getElementById('p1_unknown_time');
                const timeEl = document.getElementById('p1_birth_time');
                if (unknownCb && data.unknown_time) {
                    unknownCb.checked = true;
                    if (timeEl) timeEl.disabled = true;
                }
            }
        } catch (e) {}
    }

    if (form) {
        form.addEventListener('submit', function () {
            try {
                const payload = {};
                savedFields.forEach(([elId, key]) => {
                    const el = document.getElementById(elId);
                    if (el) payload[key] = el.value;
                });
                const unknownCb = document.getElementById('p1_unknown_time');
                payload.unknown_time = !!(unknownCb && unknownCb.checked);
                localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
            } catch (e) {}
        });
    }
})();
</script>
@endpush
@endonce
