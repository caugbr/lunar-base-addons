<button type="button"
    onclick="window.dispatchEvent(new CustomEvent('modal-open', { detail: { id: 'qr-modal-{{ $type }}-{{ $item->id }}' } }))"
    class="admin-btn admin-btn-secondary"
    title="Ver QR Code de Compartilhamento">
    <x-lucide-qr-code class="lucid-icon" /> QR Code
</button>

<x-modal id="qr-modal-{{ $type }}-{{ $item->id }}" title="QR Code: {{ $title }}" size="md">
    @include('qrcode::modal-content', ['type' => $type, 'item' => $item, 'url' => $url])
</x-modal>
