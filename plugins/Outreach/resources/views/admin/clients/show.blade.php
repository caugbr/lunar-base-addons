@extends('admin.layout')

@section('header_title', $client->name)
@section('header_subtitle', $client->company ? "Empresa: {$client->company}" : 'Contato de Prospecção')

@push('styles')
<style>
    .timeline-list {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .timeline-card {
        border-left: 3px solid var(--color-primary, #0056b3);
        padding-left: 1rem;
    }

    .timeline-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.8125rem;
        color: var(--color-text-muted, #64748b);
        margin-bottom: 0.35rem;
    }

    .timeline-delete-btn {
        background: transparent;
        border: none;
        color: var(--color-danger, #dc2626);
        cursor: pointer;
        padding: 0;
        opacity: 0.7;
        transition: opacity 0.15s ease;
    }

    .timeline-delete-btn:hover {
        opacity: 1;
    }

    .timeline-card-body {
        font-size: 0.9375rem;
        line-height: 1.5;
        white-space: pre-wrap;
        color: var(--color-text, #1e293b);
    }

    .client-data-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        font-size: 0.875rem;
    }

    .client-data-list li {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .client-data-label {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--color-text-muted, #64748b);
        letter-spacing: 0.05em;
    }

    .client-data-value {
        color: var(--color-text, #0f172a);
        word-break: break-word;
    }
</style>
@endpush

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-user class="lucid-icon" /> {{ $client->name }}</h2>
        <div class="top-buttons">
            <a href="{{ route('admin.outreach.clients.edit', $client->id) }}" class="admin-btn admin-btn-primary">
                <x-lucide-pencil class="lucid-icon" /> <span>Editar Contato</span>
            </a>
            <a href="{{ route('admin.outreach.clients.index') }}" class="admin-btn admin-btn-secondary">
                <x-lucide-arrow-left class="lucid-icon" /> <span>Voltar</span>
            </a>
        </div>
    </div>

    <div class="edit-page-inner">
        {{-- ========================================= --}}
        {{-- COLUNA PRINCIPAL: NOTAS E HISTÓRICO       --}}
        {{-- ========================================= --}}
        <div class="main-column">

            {{-- Box 1: Formulário para Adicionar Nova Anotação --}}
            <div class="edit-box">
                <header>
                    <x-lucide-message-square-plus class="lucid-icon" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 4px;" />
                    Nova Anotação / Histórico
                </header>
                <article>
                    <x-form method="POST" :action="route('admin.outreach.clients.notes.store', $client->id)">
                        <x-form.textarea
                            name="content"
                            placeholder="Registre o que aconteceu: telefonema, retorno agendado, dúvidas do lead, pedidos..."
                            rows="3"
                            required
                        />
                        <div class="buttons" style="margin-top: 10px; justify-content: flex-end;">
                            <x-form.button type="submit" variant="primary">
                                <x-lucide-save class="lucid-icon" /> Salvar Anotação
                            </x-form.button>
                        </div>
                    </x-form>
                </article>
            </div>

            {{-- Box 2: Timeline de Anotações Realizadas --}}
            <div class="edit-box">
                <header>
                    <x-lucide-history class="lucid-icon" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 4px;" />
                    Linha do Tempo de Anotações ({{ $client->notes->count() }})
                </header>
                <article>
                    @if($client->notes->isNotEmpty())
                        <div class="timeline-list">
                            @foreach($client->notes as $note)
                                <div class="timeline-card">
                                    <div class="timeline-card-header">
                                        <span>
                                            <strong>{{ $note->user ? $note->user->name : 'Sistema' }}</strong> &bull; {{ $note->created_at->format('d/m/Y H:i') }}
                                        </span>
                                        <form method="POST" action="{{ route('admin.outreach.clients.notes.destroy', [$client->id, $note->id]) }}" data-confirm="Excluir esta anotação?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="timeline-delete-btn" title="Excluir nota">
                                                <x-lucide-trash-2 class="lucid-icon" style="width: 14px; height: 14px;" />
                                            </button>
                                        </form>
                                    </div>
                                    <div class="timeline-card-body">{{ $note->content }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="admin-text-muted admin-text-center" style="padding: 1rem 0; margin: 0;">
                            Nenhuma anotação registrada ainda. Use o campo acima para adicionar a primeira.
                        </p>
                    @endif
                </article>
            </div>

            {{-- Box 3: Histórico de E-mails Enviados --}}
            <div class="edit-box">
                <header>
                    <x-lucide-mail class="lucid-icon" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 4px;" />
                    E-mails Disparados ({{ $client->emailLogs->count() }})
                </header>
                <article>
                    @if($client->emailLogs->isNotEmpty())
                        <div class="table-wrap">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Assunto</th>
                                        <th>Status</th>
                                        <th>Data de Envio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($client->emailLogs as $log)
                                    <tr>
                                        <td><strong>{{ $log->subject }}</strong></td>
                                        <td>
                                            @if($log->status === 'sent')
                                                <span class="admin-badge admin-badge-active">Enviado</span>
                                            @elseif($log->status === 'queued')
                                                <span class="admin-badge admin-badge-info">Na Fila</span>
                                            @else
                                                <span class="admin-badge admin-badge-inactive" title="{{ $log->error_message }}">Falhou</span>
                                            @endif
                                        </td>
                                        <td>{{ $log->sent_at ? $log->sent_at->format('d/m/Y H:i') : '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="admin-text-muted admin-text-center" style="padding: 1rem 0; margin: 0;">
                            Nenhum e-mail disparado para este contato até o momento.
                        </p>
                    @endif
                </article>
            </div>

        </div>

        {{-- ========================================= --}}
        {{-- COLUNA LATERAL (ASIDE): DADOS DO LEAD     --}}
        {{-- ========================================= --}}
        <div class="aside-column">
            <div class="edit-box">
                <header>Ficha do Lead</header>
                <article>
                    <ul class="client-data-list">
                        <li>
                            <span class="client-data-label">Status</span>
                            <span class="client-data-value">
                                <span class="admin-badge admin-badge-info">{{ ucfirst(str_replace('_', ' ', $client->status)) }}</span>
                            </span>
                        </li>

                        <li>
                            <span class="client-data-label">E-mail</span>
                            <span class="client-data-value">
                                <a href="mailto:{{ $client->email }}">{{ $client->email }}</a>
                            </span>
                        </li>

                        <li>
                            <span class="client-data-label">Empresa</span>
                            <span class="client-data-value">{{ $client->company ?: '-' }}</span>
                        </li>

                        <li>
                            <span class="client-data-label">Cargo</span>
                            <span class="client-data-value">{{ $client->role ?: '-' }}</span>
                        </li>

                        <li>
                            <span class="client-data-label">Telefone</span>
                            <span class="client-data-value">{{ $client->phone ?: '-' }}</span>
                        </li>

                        <li>
                            <span class="client-data-label">Website</span>
                            <span class="client-data-value">
                                @if($client->website)
                                    <a href="{{ $client->website }}" target="_blank" rel="noopener">{{ $client->website }}</a>
                                @else - @endif
                            </span>
                        </li>

                        <li style="margin-top: 0.5rem; padding-top: 0.75rem; border-top: 1px solid var(--color-border, #e2e8f0);">
                            <span class="client-data-label">Último Contato</span>
                            <span class="client-data-value" style="font-size: 0.8125rem; color: var(--color-text-muted, #64748b);">
                                {{ $client->last_contacted_at ? $client->last_contacted_at->format('d/m/Y H:i') : 'Nunca contatado' }}
                            </span>
                        </li>
                    </ul>
                </article>
            </div>
        </div>
    </div>
</div>
@endsection
