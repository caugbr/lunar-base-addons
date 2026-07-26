<button type="button"
    onclick="window.dispatchEvent(new CustomEvent('modal-open', { detail: { id: 'qr-modal-{{ $type }}-{{ $item->id }}' } }))"
    class="admin-btn admin-btn-secondary faq-table-actions"
    style="padding: 4px 12px; display: inline-flex; align-items: center;"
    title="Ver QR Code">
    <x-lucide-qr-code class="lucid-icon" />
</button>

<x-modal id="qr-modal-{{ $type }}-{{ $item->id }}" title="QR Code: {{ $title }}" size="md">
    @include('qrcode::modal-content', ['type' => $type, 'item' => $item, 'url' => $url])
</x-modal>
