@extends('admin.layout')

@section('header_title', 'Revisão de Envio')
@section('header_subtitle', 'Confira os dados antes de iniciar o envio escalonado')

@push('styles')
<style>
    .dispatch-preview-wrap {
        padding: 1.5rem;
    }

    .dispatch-safety-banner {
        background-color: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
        padding: 0.875rem 1rem;
        border-radius: 6px;
        margin-bottom: 1.5rem;
        font-size: 0.9375rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .dispatch-preview-card {
        border: 1px solid var(--color-border, #e5e7eb);
        border-radius: 8px;
        padding: 1.25rem;
        background-color: #ffffff;
        margin-bottom: 1.5rem;
    }

    .dispatch-preview-subject {
        margin: 0 0 0.75rem;
        font-size: 1rem;
    }

    .dispatch-preview-divider {
        border: 0;
        border-top: 1px solid var(--color-border, #e5e7eb);
        margin: 0.75rem 0 1rem;
    }

    .dispatch-preview-body {
        line-height: 1.6;
        color: var(--color-text, #374151);
    }

    .dispatch-actions-bar {
        display: flex;
        gap: 0.75rem;
        align-items: center;
    }
</style>
@endpush

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-send class="lucid-icon" /> Confirmar Disparo para {{ $clients->count() }} Contatos</h2>
    </div>

    <div class="dispatch-preview-wrap">
        <div class="dispatch-safety-banner">
            <x-lucide-shield-alert class="lucid-icon" />
            <span>
                Os e-mails serão enviados via fila com <strong>intervalos aleatórios de 45 a 90 segundos</strong> entre cada destinatário. Isso protege a reputação do seu domínio e evita filtros de spam no Brevo.
            </span>
        </div>

        @if($firstPreview)
        <div>
            <h3>Exemplo real com o primeiro destinatário ({{ $clients->first()->name }}):</h3>
            <div class="dispatch-preview-card">
                <div class="dispatch-preview-subject">
                    <strong>Assunto:</strong> {{ $firstPreview['subject'] }}
                </div>
                <hr class="dispatch-preview-divider">
                <div class="dispatch-preview-body">
                    {!! $firstPreview['body'] !!}
                </div>
            </div>
        </div>
        @endif

        <x-form method="POST" :action="route('admin.outreach.dispatch.send')">
            <input type="hidden" name="template_id" value="{{ $template->id }}">
            @foreach($clients as $c)
                <input type="hidden" name="client_ids[]" value="{{ $c->id }}">
            @endforeach

            <div class="dispatch-actions-bar">
                <x-form.button type="submit" icon="send">
                    Confirmar e Iniciar Envio
                </x-form.button>
                <a href="{{ route('admin.outreach.clients.index') }}" class="admin-btn admin-btn-secondary">
                    Cancelar
                </a>
            </div>
        </x-form>
    </div>
</div>
@endsection
