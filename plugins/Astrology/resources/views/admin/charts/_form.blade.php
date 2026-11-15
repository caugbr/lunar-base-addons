@once
@push('styles')
<link rel="stylesheet" href="{{ asset('plugins/astrology/css/astrology-admin.css') }}">
@endpush
@endonce

<div class="admin-card">
    <div class="admin-card-header">
        <h2>
            <x-lucide-compass class="lucid-icon" />
            {{ isset($chart) ? 'Editar Mapa Astral' : 'Calcular Novo Mapa Astral' }}
        </h2>
        <a href="{{ route('admin.astrology.charts.index') }}" class="admin-btn admin-btn-secondary">
            <x-lucide-arrow-left class="lucid-icon" /> Voltar
        </a>
    </div>

    <form method="POST" action="{{ $action }}" id="chart_form">
        @csrf
        @if(isset($chart))
            @method('PUT')
        @endif

        {{-- 1. IDENTIFICAÇÃO E DATA/HORA --}}
        <div class="settings-group">
            <div class="group-header">
                <h3 class="group-title">
                    <x-lucide-user class="lucid-icon" /> Dados de Nascimento
                </h3>
            </div>

            <div class="admin-form-row">
                <div class="form-group admin-form-col-2">
                    <label class="field-label" for="name">Nome da Pessoa / Título do Mapa *</label>
                    <input type="text" name="name" id="name" class="form-input"
                        value="{{ old('name', $chart->name ?? '') }}" placeholder="Ex: Maria dos Santos" required>
                    @error('name') <small class="error">{{ $message }}</small> @enderror
                </div>

                <div class="form-group admin-form-col-1">
                    <label class="field-label" for="slug">Slug (URL)</label>
                    <input type="text" name="slug" id="slug" class="form-input"
                        value="{{ old('slug', $chart->slug ?? '') }}" placeholder="maria-dos-santos">
                    <small class="form-help">Deixe em branco para gerar automaticamente.</small>
                    @error('slug') <small class="error">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="admin-form-row">
                <div class="form-group">
                    <label class="field-label" for="birth_date">Data de Nascimento *</label>
                    <input type="date" name="birth_date" id="birth_date" class="form-input"
                        value="{{ old('birth_date', isset($chart) ? $chart->birth_date->format('Y-m-d') : '') }}" required>
                    @error('birth_date') <small class="error">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label class="field-label" for="birth_time">Hora Exata (HH:MM) *</label>
                    <input type="time" name="birth_time" id="birth_time" class="form-input"
                        value="{{ old('birth_time', isset($chart) ? substr($chart->birth_time, 0, 5) : '12:00') }}" required>
                    @error('birth_time') <small class="error">{{ $message }}</small> @enderror
                </div>
            </div>
        </div>

        {{-- 2. LOCALIZAÇÃO GEOGRÁFICA --}}
        <div class="settings-group admin-form-divider">
            <div class="group-header">
                <h3 class="group-title">
                    <x-lucide-map-pin class="lucid-icon" /> Local de Nascimento (Coordenadas)
                </h3>
            </div>

            <div class="admin-form-row">
                <div class="form-group admin-form-col-2">
                    <label class="field-label" for="city">Cidade / Estado / País (opcional)</label>
                    <x-cities-autocomplete
                        name="city"
                        id="city"
                        :value="old('city', $chart->city ?? '')"
                        placeholder="Digite ao menos 2 letras da cidade..."
                        lat-target="#latitude"
                        lng-target="#longitude"
                        timezone-target="#timezone"
                    />
                    <small class="form-help">Ao selecionar na lista, latitude, longitude e fuso horário são preenchidos automaticamente.</small>
                    @error('city') <small class="error">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="admin-form-row">
                <div class="form-group">
                    <label class="field-label" for="latitude">Latitude Decimal *</label>
                    <input type="number" step="0.0000001" name="latitude" id="latitude" class="form-input"
                        value="{{ old('latitude', $chart->latitude ?? '') }}" required>
                    <small class="form-help">Sul é negativo. Ex: -22.9068</small>
                    @error('latitude') <small class="error">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label class="field-label" for="longitude">Longitude Decimal *</label>
                    <input type="number" step="0.0000001" name="longitude" id="longitude" class="form-input"
                        value="{{ old('longitude', $chart->longitude ?? '') }}" required>
                    <small class="form-help">Oeste é negativo. Ex: -43.1729</small>
                    @error('longitude') <small class="error">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label class="field-label" for="timezone">Fuso Horário *</label>
                    <input type="text" name="timezone" id="timezone" class="form-input"
                        value="{{ old('timezone', $chart->timezone ?? 'America/Sao_Paulo') }}" required>
                    <small class="form-help">Preenchido automaticamente ao escolher a cidade (padrão: America/Sao_Paulo).</small>
                    @error('timezone') <small class="error">{{ $message }}</small> @enderror
                </div>
            </div>
        </div>

        {{-- 3. CONFIGURAÇÕES ASTROLÓGICAS E ORBES --}}
        <div class="settings-group admin-form-divider">
            <div class="group-header">
                <h3 class="group-title">
                    <x-lucide-settings class="lucid-icon" /> Parâmetros de Cálculo e Orbes
                </h3>
            </div>

            <div class="admin-form-row">
                <div class="form-group">
                    <label class="field-label" for="house_system">Sistema de Casas</label>
                    <select name="house_system" id="house_system" class="form-input">
                        @php $currentHsys = old('house_system', $chart->house_system ?? 'placidus'); @endphp
                        <option value="placidus" {{ $currentHsys === 'placidus' ? 'selected' : '' }}>Placidus (Mais comum)</option>
                        <option value="koch" {{ $currentHsys === 'koch' ? 'selected' : '' }}>Koch</option>
                        <option value="whole_sign" {{ $currentHsys === 'whole_sign' ? 'selected' : '' }}>Signos Inteiros (Whole Sign)</option>
                        <option value="equal" {{ $currentHsys === 'equal' ? 'selected' : '' }}>Casas Iguais (Equal)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="field-label" for="orb_general">Orbe Geral (graus) *</label>
                    <input type="number" step="0.5" min="0.5" max="15" name="orb_general" id="orb_general" class="form-input"
                        value="{{ old('orb_general', $chart->orb_general ?? 6.0) }}" required>
                    <small class="form-help">Tolerância para planetas normais (padrão: 6°).</small>
                    @error('orb_general') <small class="error">{{ $message }}</small> @enderror
                </div>

                <div class="form-group">
                    <label class="field-label" for="orb_luminaries">Orbe Sol e Lua (graus) *</label>
                    <input type="number" step="0.5" min="0.5" max="15" name="orb_luminaries" id="orb_luminaries" class="form-input"
                        value="{{ old('orb_luminaries', $chart->orb_luminaries ?? 10.0) }}" required>
                    <small class="form-help">Tolerância maior para luminares (padrão: 10°).</small>
                    @error('orb_luminaries') <small class="error">{{ $message }}</small> @enderror
                </div>
            </div>

            <div class="admin-form-row">
                <div class="form-group admin-form-col-fixed">
                    <label class="field-label">Aspectos Menores</label>
                    <x-switch name="include_minors" id="include_minors"
                        checked="{{ old('include_minors', $chart->include_minors ?? false) }}"
                        active="Sim" inactive="Não" />
                    <small class="form-help">Quincúncio, Semisextil, etc.</small>
                </div>

                <div class="form-group admin-form-col-fixed">
                    <label class="field-label">Disponível Publicamente?</label>
                    <x-switch name="is_public" id="is_public"
                        checked="{{ old('is_public', $chart->is_public ?? false) }}"
                        active="Sim" inactive="Não" />
                    <small class="form-help">Permite exibir via shortcode/API.</small>
                </div>
            </div>
        </div>

        <div class="buttons form-buttons-top">
            <button type="submit" class="admin-btn admin-btn-primary">
                <x-lucide-save class="lucid-icon" />
                {{ isset($chart) ? 'Salvar e Recalcular Mapa' : 'Calcular e Salvar Mapa' }}
            </button>
        </div>
    </form>
</div>

<x-lost-changes-warn selector="#chart_form" />

@push('scripts')
<script>
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    if (nameInput && slugInput) {
        nameInput.addEventListener('input', function() {
            if (!slugInput.dataset.manuallyEdited) {
                slugInput.value = this.value.toString().toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s_-]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });
        slugInput.addEventListener('input', function() {
            this.dataset.manuallyEdited = 'true';
        });
    }
</script>
@endpush
