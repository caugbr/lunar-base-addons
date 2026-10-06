@extends('admin.layout')

@section('header_title', 'Mapa de Interações')
@section('header_subtitle', 'Interações de mouse e rolagem para: ' . $path)

@section('content')
<div class="heatmap-viewer-container">
    {{-- BARRA DE CONTROLE SUPERIOR (DOIS ANDARES) --}}
    <div class="heatmap-toolbar-box">
        {{-- LINHA 1: INFORMAÇÃO, DISPOSITIVO E VOLTAR --}}
        <div class="heatmap-toolbar-row">
            <div class="toolbar-left">
                <div class="clicks-counter">
                    <x-lucide-mouse-pointer-click class="lucid-icon" />
                    <span id="click-count-text">Carregando...</span>
                </div>
            </div>

            <div class="toolbar-center">
                <div class="device-toggle">
                    <button type="button" id="btn-device-desktop" class="transparent-btn toggle-btn active" onclick="switchDevice('desktop')" title="Visualização Desktop">
                        <x-lucide-monitor class="lucid-icon" />
                    </button>
                    <button type="button" id="btn-device-mobile" class="transparent-btn toggle-btn" onclick="switchDevice('mobile')" title="Visualização Mobile">
                        <x-lucide-smartphone class="lucid-icon" />
                    </button>
                </div>
            </div>

            <div class="toolbar-right">
                <a href="{{ route('admin.heatmap.index', ['days' => $days]) }}" class="admin-btn admin-btn-secondary">
                    <x-lucide-arrow-left class="lucid-icon" /> Voltar
                </a>
            </div>
        </div>

        {{-- LINHA 2: TIPO DE MAPA (MODOS) E PERÍODO --}}
        <div class="heatmap-toolbar-row toolbar-subrow">
            {{-- Seletor dos 3 Modos de Análise --}}
            <div class="mode-toggle">
                <button type="button" id="btn-mode-click" class="transparent-btn mode-btn active" onclick="switchMode('click')">
                    <x-lucide-mouse-pointer-click class="lucid-icon" />
                    <span>Cliques</span>
                </button>
                <button type="button" id="btn-mode-hover" class="transparent-btn mode-btn" onclick="switchMode('hover')">
                    <x-lucide-eye class="lucid-icon" />
                    <span>Atenção</span>
                </button>
                <button type="button" id="btn-mode-scroll" class="transparent-btn mode-btn" onclick="switchMode('scroll')">
                    <x-lucide-arrow-down-up class="lucid-icon" />
                    <span>Rolagem</span>
                </button>
            </div>

            {{-- Filtro de Período --}}
            <div class="toolbar-filter">
                <select id="select-days" onchange="changeDays(this.value)" class="admin-filter-select">
                    <option value="7" {{ $days == 7 ? 'selected' : '' }}>Últimos 7 dias</option>
                    <option value="30" {{ $days == 30 ? 'selected' : '' }}>Últimos 30 dias</option>
                    <option value="90" {{ $days == 90 ? 'selected' : '' }}>Últimos 90 dias</option>
                    <option value="0" {{ $days == 0 ? 'selected' : '' }}>Todo o período</option>
                </select>
            </div>
        </div>
    </div>

    {{-- PALCO DO MAPA (IFRAME + CANVAS) --}}
    <div class="heatmap-stage-wrapper">
        <div class="heatmap-stage stage-desktop" id="stage">
            <iframe src="{{ url($path) }}" id="heatmap-frame" scrolling="no"></iframe>
            {{-- Canvas sobreposto atuando como escudo e desenhando as manchas térmicas --}}
            <canvas id="heatmap-canvas"></canvas>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .heatmap-viewer-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    /* TOOLBAR DE DOIS ANDARES */
    .heatmap-toolbar-box {
        display: flex;
        flex-direction: column;
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid var(--color-border, #e2e8f0);
        overflow: hidden;
    }
    .heatmap-toolbar-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 1.25rem;
    }
    .toolbar-subrow {
        border-top: 1px solid var(--color-border, #f1f5f9);
        background: #fafafa;
        padding: 0.5rem 1.25rem;
    }

    .toolbar-left, .toolbar-right {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .toolbar-subrow select {
        -webkit-appearance: none; /* Chrome, Safari, Edge, Opera */
        -moz-appearance: none;    /* Firefox */
        appearance: none;         /* Padrão W3C moderno */
        background-image: none;   /* Garante que não haja seta SVG injetada por frameworks */
    }

    /* Arranca a setinha teimosa no Edge/IE */
    .toolbar-subrow select::-ms-expand {
        display: none;
    }

    /* DISPOSITIVOS */
    .device-toggle, .mode-toggle {
        display: flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 6px;
        gap: 4px;
    }
    .toggle-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #475569;
        border-radius: 4px;
        transition: all 0.2s;
        cursor: pointer;
    }
    .toggle-btn.active {
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    /* SELETOR DE MODOS (CLIQUES / ATENÇÃO / ROLAGEM) */
    .mode-btn {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        font-size: 0.785rem;
        font-weight: 600;
        color: #64748b;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .mode-btn .lucid-icon {
        width: 14px;
        height: 14px;
    }
    .mode-btn.active {
        background: #ffffff;
        color: #0f172a;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .clicks-counter {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85rem;
        color: var(--color-text-muted);
    }
    .toolbar-filter select {
        padding: 4px 8px;
        font-size: 0.8rem;
    }

    /* WRAPPER EXTERNO */
    .heatmap-stage-wrapper {
        width: 100%;
        display: flex;
        justify-content: center;
        overflow: hidden;
        padding-bottom: 2rem;
    }

    /* PALCO ONDE O SITE E O CANVAS FICAM SOBREPOSTOS */
    .heatmap-stage {
        position: relative;
        background: #ffffff;
        border: 1px solid var(--color-border, #e2e8f0);
        border-radius: 8px;
        overflow: hidden;
        margin: 0 auto;
        transition: width 200ms ease 0s;
    }

    .stage-desktop {
        width: 100%;
    }

    .stage-mobile {
        width: 390px !important;
        box-shadow: 0 4px 25px rgba(0, 0, 0, 0.12);
    }

    #heatmap-frame {
        width: 100%;
        border: none;
        display: block;
    }

    #heatmap-canvas {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 20;
        cursor: default;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('plugins/heatmap/js/canvas-heatmap.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const frame = document.getElementById('heatmap-frame');
    const canvas = document.getElementById('heatmap-canvas');
    const stage = document.getElementById('stage');
    const clickCountText = document.getElementById('click-count-text');
    const btnDesktop = document.getElementById('btn-device-desktop');
    const btnMobile = document.getElementById('btn-device-mobile');

    const path = "{{ $path }}";
    let days = "{{ $days }}";
    let currentDevice = 'desktop';
    let currentMode = 'click'; // 'click' | 'hover' | 'scroll'

    let rawData = null;
    let currentHeight = 2000;

    // 1. ALTERNÂNCIA DE DISPOSITIVO (DESKTOP / MOBILE)
    window.switchDevice = function(newDevice) {
        if (currentDevice === newDevice) return;
        currentDevice = newDevice;

        btnDesktop.classList.toggle('active', currentDevice === 'desktop');
        btnMobile.classList.toggle('active', currentDevice === 'mobile');

        stage.classList.toggle('stage-desktop', currentDevice === 'desktop');
        stage.classList.toggle('stage-mobile', currentDevice === 'mobile');

        setTimeout(() => {
            recalculateDimensions();
            renderCurrentMode();
        }, 250);
    };

    // 2. ALTERNÂNCIA DE MODO (CLIQUES / ATENÇÃO / ROLAGEM)
    window.switchMode = function(newMode) {
        if (currentMode === newMode) return;
        currentMode = newMode;

        document.querySelectorAll('.mode-btn').forEach(btn => btn.classList.remove('active'));
        const activeBtn = document.getElementById(`btn-mode-${newMode}`);
        if (activeBtn) activeBtn.classList.add('active');

        fetchHeatmapData();
    };

    // 3. ALTERAÇÃO DE PERÍODO (DIAS)
    window.changeDays = function(newDays) {
        days = newDays;
        fetchHeatmapData();
    };

    function recalculateDimensions() {
        try {
            const doc = frame.contentDocument || frame.contentWindow.document;
            currentHeight = Math.max(
                doc.body.scrollHeight,
                doc.documentElement.scrollHeight,
                1000
            );

            frame.style.height = currentHeight + 'px';
            canvas.style.height = currentHeight + 'px';
        } catch (e) {
            currentHeight = 2500;
            frame.style.height = currentHeight + 'px';
            canvas.style.height = currentHeight + 'px';
        }
    }

    frame.addEventListener('load', function () {
        recalculateDimensions();
        fetchHeatmapData();
    });

    // 4. BUSCA DINÂMICA CONFORME O MODO ATUAL
    function fetchHeatmapData() {
        clickCountText.innerText = 'Carregando...';

        fetch(`{{ route('admin.heatmap.data') }}?path=${encodeURIComponent(path)}&mode=${currentMode}&days=${days}`)
            .then(res => res.json())
            .then(data => {
                rawData = data;

                if (currentMode === 'scroll') {
                    clickCountText.innerText = `${data.total} visualizações de rolagem (Média: ${data.average || 0}%)`;
                } else if (currentMode === 'hover') {
                    clickCountText.innerText = `${data.total} atenções em ${data.elements?.length || 0} elementos`;
                } else {
                    clickCountText.innerText = `${data.total} cliques em ${data.elements?.length || 0} elementos`;
                }

                renderCurrentMode();
            })
            .catch(err => {
                console.error('Erro ao carregar dados:', err);
                clickCountText.innerText = 'Erro ao carregar dados';
            });
    }

    // 5. RENDERIZAÇÃO DO MODO ATIVO NO CANVAS
    function renderCurrentMode() {
        if (!rawData) return;

        try {
            const doc = frame.contentDocument || frame.contentWindow.document;
            const stageWidth = stage.offsetWidth;

            const heatmap = new ElementHeatmap(canvas, {
                radius: currentDevice === 'mobile' ? 18 : 28,
                opacity: currentMode === 'hover' ? 0.55 : 0.70
            });

            if (currentMode === 'scroll') {
                // Desenha o mapa de profundidade de rolagem
                heatmap.renderScroll(rawData.distribution || [], stageWidth, currentHeight);
            } else {
                // Desenha os pontos semânticos (click ou hover)
                heatmap.render(rawData.elements || [], doc, stageWidth, currentHeight);
            }
        } catch (e) {
            console.error('Falha ao renderizar no canvas:', e);
        }
    }

    window.addEventListener('resize', () => {
        recalculateDimensions();
        renderCurrentMode();
    });
});
</script>
@endpush
