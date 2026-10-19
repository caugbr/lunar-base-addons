@once
<link rel="stylesheet" href="{{ asset('plugins/whatsapp-button/css/whatsapp-button.css') }}">
@endonce

<div class="lb-whatsapp-wrap pos-{{ $position }} {{ !$isOnline ? 'is-offline' : '' }}">

    {{-- Balão de Chamada --}}
    @if(!$isOnline)
        <div class="lb-whatsapp-tooltip">{{ $offlineMessage }}</div>
    @elseif(!empty($tooltip))
        <div class="lb-whatsapp-tooltip">{{ $tooltip }}</div>
    @endif

    {{-- Link / Botão --}}
    @if($isOnline)
        <a href="{{ $url }}"
           class="lb-whatsapp-btn {{ $pulse ? 'lb-whatsapp-pulse' : '' }}"
           target="_blank"
           rel="noopener noreferrer"
           aria-label="Abrir conversa no WhatsApp">
            <img src="{{ asset('plugins/whatsapp-button/images/whatsapp.svg') }}"
                 alt="WhatsApp"
                 class="lb-whatsapp-icon"
                 width="60"
                 height="60">
        </a>
    @else
        {{-- Botão desabilitado (o CSS aplica filtro cinza automático) --}}
        <div class="lb-whatsapp-btn" title="{{ $offlineMessage }}">
            <img src="{{ asset('plugins/whatsapp-button/images/whatsapp.svg') }}"
                 alt="WhatsApp"
                 class="lb-whatsapp-icon"
                 width="60"
                 height="60">
        </div>
    @endif

</div>
