@extends('admin.layout')

@section('header_title', 'Novo Modelo')
@section('header_subtitle', 'Crie um modelo com tags dinâmicas para prospecção')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-file-plus-corner class="lucid-icon" /> Novo Modelo</h2>
        <div class="top-buttons">

            <a href="{{ route('admin.outreach.templates.index') }}" class="admin-btn admin-btn-secondary">
                <x-lucide-arrow-left class="lucid-icon" /> <span>Voltar</span>
            </a>
        </div>
    </div>

    @include('outreach::admin.templates._form', [
        'template' => $template,
        'action'   => route('admin.outreach.templates.store'),
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
