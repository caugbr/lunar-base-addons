@extends('admin.layout')
@section('header_title', 'Gerador de QR Code')
@section('header_subtitle', 'Crie imagens vetoriais de QR Code para URLs ou textos customizados')
@section('content')

@once
@push('styles')
<link rel="stylesheet" href="{{ asset('plugins/qr-code/css/qrcode-admin.css') }}">
@endpush
@endonce

<div class="admin-card" x-data="qrCodeApp()" x-cloak>
    <div class="admin-card-header">
        <h2><x-lucide-qr-code class="lucid-icon" /> Gerador Avulso</h2>
    </div>

    {{-- O formulário envolve todo o grid para que o botão de download submeta os dados --}}
    <form method="POST" action="{{ route('admin.qrcode.download') }}" id="qrcode_form">
        @csrf

        <div class="qrcode-generator-grid">

            {{-- Painel de Configurações (Esquerda) --}}
            <div class="qrcode-settings-panel">
                <div class="form-group">
                    <label for="content">Conteúdo ou URL *</label>
                    <textarea name="content"
                              id="content"
                              x-model.debounce.500ms="content"
                              placeholder="https://seusite.com/post ou digite qualquer texto..."
                              class="form-input"
                              rows="5"
                              required></textarea>
                    <small class="form-help">Recomendado manter até 300 caracteres para melhor leitura em celulares.</small>
                </div>

                <div class="admin-form-row">
                    <div class="form-group" style="flex: 1;">
                        <label for="size">Dimensão do SVG (px)</label>
                        <select name="size" id="size" x-model="size" class="form-input">
                            <option value="200">200 x 200 px</option>
                            <option value="300" selected>300 x 300 px</option>
                            <option value="500">500 x 500 px</option>
                            <option value="800">800 x 800 px (Impressão)</option>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label for="filename">Nome do Arquivo</label>
                        <input type="text" name="filename" id="filename" x-model="filename" placeholder="qrcode-site" class="form-input">
                    </div>
                </div>
            </div>

            {{-- Painel de Pré-visualização e Download (Direita) --}}
            <div class="qrcode-preview-card">
                <span class="qrcode-preview-title">Pré-visualização</span>

                {{-- Caixa da Imagem Centralizada com Margens --}}
                <div class="qrcode-preview-box">
                    <template x-if="content.trim()">
                        <div x-html="svgPreview"></div>
                    </template>
                    <template x-if="!content.trim()">
                        <div class="qrcode-preview-placeholder">
                            Digite um texto ou URL para gerar o QR Code
                        </div>
                    </template>
                </div>

                {{-- Botão de Download posicionado abaixo da imagem --}}
                <div class="qrcode-download-action">
                    <button type="submit" class="admin-btn admin-btn-primary" :disabled="!content.trim()">
                        <x-lucide-download class="lucid-icon" /> Baixar Imagem (SVG)
                    </button>
                </div>

                <div class="qrcode-meta-info" x-show="content.trim()">
                    <span x-text="content.length + ' caractere(s)'"></span>
                </div>
            </div>

        </div>
    </form>
</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script>
    function qrCodeApp() {
        return {
            content: '{{ url("/") }}',
            size: '300',
            filename: 'qrcode-site',
            svgPreview: '',

            init() {
                this.updatePreview();
                this.$watch('content', () => this.updatePreview());
                this.$watch('size', () => this.updatePreview());
            },

            updatePreview() {
                if (!this.content.trim()) {
                    this.svgPreview = '';
                    return;
                }

                const encoded = encodeURIComponent(this.content);
                this.svgPreview = `<img src="https://api.qrserver.com/v1/create-qr-code/?data=${encoded}&size=220x220&format=svg" alt="QR Code Preview">`;
            }
        }
    }
</script>
@endpush
@endsection
