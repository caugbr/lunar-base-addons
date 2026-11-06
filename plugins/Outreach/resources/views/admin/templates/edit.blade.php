@extends('admin.layout')

@section('header_title', 'Editar Modelo')
@section('header_subtitle', "Atualizando: {$template->name}")

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-file-pen class="lucid-icon" /> Editar Modelo</h2>
        <div class="top-buttons">
            <a href="{{ route('admin.outreach.templates.index') }}" class="admin-btn admin-btn-secondary">
                <x-lucide-arrow-left class="lucid-icon" /> <span>Voltar</span>
            </a>
        </div>
    </div>

    @include('outreach::admin.templates._form', [
        'template' => $template,
        'action'   => route('admin.outreach.templates.update', $template->id),
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
