<style>
    /* Container principal centralizado */
    .qr-modal-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 0.5rem 0;
        box-sizing: border-box;
    }

    /* Caixa branca da Imagem do QR Code centralizada */
    .qr-modal-preview-box {
        background-color: #ffffff;
        padding: 1.25rem;
        border-radius: 12px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem; /* Margem inferior para afastar do input */
        max-width: 100%;
    }

    .qr-modal-preview-box svg {
        width: 200px;
        height: 200px;
        max-width: 100%;
        display: block;
    }

    /* Linha do Input + Botão Copiar centralizado */
    .qr-modal-input-group {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        max-width: 380px; /* Largura máxima harmoniosa */
        gap: 0.5rem;
        margin-bottom: 1.25rem; /* Margem entre o input e o botão de download */
    }

    .qr-modal-input-group input {
        flex: 1;
        font-family: monospace;
        font-size: 0.85rem;
        text-align: center; /* Texto da URL centralizado */
    }

    /* Formulário e Botão de Download */
    .qr-modal-action-form {
        width: 100%;
        max-width: 380px;
        display: flex;
        justify-content: center;
    }

    .qr-modal-download-btn {
        width: 100%;
        justify-content: center;
    }
</style>

<div class="qr-modal-container" x-data="{ copied: false }">
    {{-- 1. Imagem do QR Code Centralizada --}}
    <div class="qr-modal-preview-box">
        {!! \Plugins\QrCode\Helpers\QrCodeHelper::svg($url, 200) !!}
    </div>

    {{-- 2. Input da URL e Botão de Copiar Centralizados --}}
    <div class="qr-modal-input-group">
        <input type="text"
               value="{{ $url }}"
               readonly
               class="form-input"
               id="qr_url_{{ $type }}_{{ $item->id }}">

        <button type="button"
                class="admin-btn admin-btn-secondary"
                @click="navigator.clipboard.writeText('{{ $url }}'); copied = true; setTimeout(() => copied = false, 2000)"
                title="Copiar URL">
            <template x-if="!copied">
                <x-lucide-copy class="lucid-icon" />
            </template>
            <template x-if="copied">
                <x-lucide-check class="lucid-icon" style="color: #10b981;" />
            </template>
        </button>
    </div>

    {{-- 3. Formulário e Botão de Download (com margem em relação ao input acima) --}}
    <form method="POST" action="{{ route('admin.qrcode.download') }}" class="qr-modal-action-form">
        @csrf
        <input type="hidden" name="content" value="{{ $url }}">
        <input type="hidden" name="filename" value="{{ $item->slug ?? 'qrcode' }}">
        <input type="hidden" name="size" value="400">

        <button type="submit" class="admin-btn admin-btn-primary qr-modal-download-btn">
            <x-lucide-download class="lucid-icon" /> Baixar Imagem (SVG)
        </button>
    </form>
</div>
