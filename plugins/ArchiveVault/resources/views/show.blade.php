@extends('admin.layout')

@section('header_title', 'Inspeção de Registro')
@section('header_subtitle', 'Snapshot completo do registro preservado no cofre')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-file-search class="lucid-icon" /> {{ $record->title }} ({{ $record->type_name }})</h2>
        <a href="{{ route('admin.archive_vault.index') }}" class="admin-btn admin-btn-secondary">
            <x-lucide-arrow-left class="lucid-icon" /> Voltar
        </a>
    </div>

    <div style="padding: 20px;">
        <h4>Conteúdo em Texto Original:</h4>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 8px; margin-bottom: 24px; max-height: 250px; overflow-y: auto;">
            {!! $record->payload['content'] ?? '<p><em>Sem conteúdo HTML</em></p>' !!}
        </div>

        <h4>Payload Completo (JSON Bruto):</h4>
        <pre style="background: #0f172a; color: #38bdf8; padding: 16px; border-radius: 8px; overflow-x: auto; font-size: 13px;">{{ json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</div>
@endsection
