@extends('admin.layout')

@section('header_title', 'Snippets Visuais')
@section('header_subtitle', 'Editar molde visual')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-pencil class="lucid-icon" /> Editar: {{ $snippet->title }}</h2>
        <div class="top-buttons">
            <x-hook
                name="admin.edit_snippet_header_buttons"
                :params="['snippet' => $snippet]"
                desc="Botões no header da edição de snippet"
            />

            <a href="{{ route('admin.visual-snippets.show', $snippet->id) }}" class="admin-btn admin-btn-secondary">
                <x-lucide-eye class="lucid-icon" /> <span>Ver</span>
            </a>

            <a href="{{ route('admin.visual-snippets.index') }}" class="admin-btn admin-btn-secondary">
                <x-lucide-arrow-left class="lucid-icon" /> <span>Voltar</span>
            </a>
        </div>
    </div>

    @include('visual-snippets::admin._form', [
        'action'  => route('admin.visual-snippets.update', $snippet->id),
        'snippet' => $snippet
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
