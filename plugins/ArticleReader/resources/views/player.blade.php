@php
    // 1. Remove tags HTML e espaços duplicados
    $plainText = trim(preg_replace('/\s+/', ' ', strip_tags($item->content)));

    // 2. Conta palavras e calcula minutos (média de 140 palavras/minuto)
    $wordCount = count(preg_split('/\s+/u', $plainText, -1, PREG_SPLIT_NO_EMPTY));
    $minutes = max(1, (int) ceil($wordCount / (int) config('reading.words_count', 140)));
@endphp

@if(!empty($plainText))
<div class="lunar-article-reader">
    <button type="button" class="lunar-reader-btn-play" title="Ouvir conteúdo">
        <x-lucide-play class="icon-play" />
        <x-lucide-pause class="icon-pause" style="display: none;" />
    </button>

    <div class="lunar-reader-body">
        <div class="lunar-reader-meta">
            <span class="lunar-reader-label">Ouvir este conteúdo</span>
            <span class="lunar-reader-time">{{ $minutes }} min</span>
        </div>
        <div class="lunar-reader-progress-track">
            <div class="lunar-reader-progress-bar"></div>
        </div>
    </div>

    <button type="button" class="lunar-reader-speed-btn" title="Velocidade de reprodução">1x</button>

    {{-- Texto 100% puro para o JavaScript ler sem depender de seletores do tema --}}
    <div class="lunar-reader-source-text" style="display: none;">{{ $plainText }}</div>
</div>
@endif
