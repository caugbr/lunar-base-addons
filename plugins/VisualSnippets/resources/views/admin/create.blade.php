@extends('admin.layout')

@section('header_title', 'Snippets Visuais')
@section('header_subtitle', 'Criar novo molde visual reutilizável')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-file-text class="lucid-icon" /> Novo Snippet</h2>
        <div class="top-buttons">
            <x-hook name="admin.create_snippet_header_buttons" desc="Botões no header da criação de snippet" />

            <a href="{{ route('admin.visual-snippets.index') }}" class="admin-btn admin-btn-secondary">
                <x-lucide-arrow-left class="lucid-icon" /> <span>Voltar</span>
            </a>
        </div>
    </div>

    @include('visual-snippets::admin._form', [
        'action' => route('admin.visual-snippets.store')
    ])
</div>

<x-media.grid-modal />
<x-media.upload-modal id="mainUploader" folder="uploads" accept="image/*,application/pdf" :max-size="10240" />
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/page-media.css') }}">
@endpush

@push('scripts')
<script>
    window.LUNAR_SHORTCODES = @json(\App\Helpers\ContentHelper::getRegisteredShortcodes());
</script>
@endpush
