@extends('admin.layout')

@section('header_title', 'Contatos')
@section('header_subtitle', 'Gerencie seus clientes e execute prospecções')

@push('styles')
<style>
/* Layout dividido: Filtros (Esquerda) e Disparo em Lote (Direita) */
.outreach-top-grid {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.outreach-top-grid > form {
    margin: 0;
}

.outreach-filter-col {
    flex: 1;
    min-width: 480px;
}

.outreach-dispatch-col {
    flex: 0 0 auto;
}

.batch-template-select {
    min-width: 220px;
}

@media (max-width: 1200px) {
    .outreach-top-grid {
        flex-direction: column;
        align-items: stretch;
    }
    .outreach-filter-col,
    .outreach-dispatch-col {
        width: 100%;
        min-width: 0;
    }
    .outreach-top-grid .admin-filters-row {
        flex-direction: column;
        align-items: stretch;
    }
    .batch-template-select {
        width: 100%;
    }
}

/* ==========================================================================
   BARRA DE ABAS DE STATUS (Lunar Base UI)
   ========================================================================== */
.content-status-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 16px;
    margin-bottom: 20px;
    background: #f8fafc;
    border: 1px solid var(--color-border, #e2e8f0);
    border-radius: 8px;
    flex-wrap: wrap;
}

.status-tabs-group {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.status-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    background: #ffffff;
    border: 1px solid var(--color-border, #cbd5e1);
    border-radius: 20px;
    color: #475569;
    font-size: 0.815rem;
    font-weight: 500;
    text-decoration: none;
    line-height: 1;
    transition: all 0.15s ease;
}

.status-tab-btn:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #0f172a;
}

.status-tab-btn.active {
    background: #0f172a;
    border-color: #0f172a;
    color: #ffffff;
}

.status-tab-btn .tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.07);
    color: inherit;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 16px;
}

.status-tab-btn.active .tab-badge {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
}

.status-tab-btn .lucid-icon {
    width: 13px;
    height: 13px;
}
</style>
@endpush

@section('content')
@if(!isset($tableExists) || !$tableExists)
    <div class="admin-card">
        <div class="admin-empty-list" style="padding: 40px; text-align: center;">
            <div style="margin-bottom: 12px;">
                <x-lucide-alert-triangle class="lucid-icon" style="color: var(--color-warning, #f59e0b); width: 36px; height: 36px;" />
            </div>
            <h3>Tabelas não encontradas</h3>
            <p class="admin-text-muted">
                A tabela <code>outreach_clients</code> ainda não foi criada no banco de dados. Execute as migrações do plugin.
            </p>
        </div>
    </div>
@else
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-users class="lucid-icon" /> Lista de Contatos</h2>
        <a href="{{ route('admin.outreach.clients.create') }}" class="admin-btn admin-btn-primary">
            <x-lucide-plus class="lucid-icon" /> <span>Novo Contato</span>
        </a>
    </div>

    {{-- Linha Superior: Filtros com Dropdown de Status (Esquerda) e Disparo em Lote (Direita) --}}
    <div class="outreach-top-grid">
        {{-- Coluna 1: Formulário de Filtro --}}
        <div class="outreach-filter-col">
            <form method="GET" action="{{ route('admin.outreach.clients.index') }}" class="admin-filters">
                <div class="admin-filters-row">
                    {{-- Busca textual --}}
                    <div class="admin-filter-group" style="flex: 2;">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            class="admin-filter-input"
                            placeholder="Buscar por nome, e-mail ou empresa..."
                        >
                    </div>

                    {{-- Dropdown de Status --}}
                    <div class="admin-filter-group" style="flex: 1;">
                        <select name="status" class="admin-filter-select">
                            <option value="">Todos os status</option>
                            <option value="novo" {{ request('status') === 'novo' ? 'selected' : '' }}>Novo</option>
                            <option value="contatado" {{ request('status') === 'contatado' ? 'selected' : '' }}>Contatado</option>
                            <option value="em_conversa" {{ request('status') === 'em_conversa' ? 'selected' : '' }}>Em Conversa</option>
                            <option value="fechado" {{ request('status') === 'fechado' ? 'selected' : '' }}>Fechado</option>
                            <option value="descartado" {{ request('status') === 'descartado' ? 'selected' : '' }}>Descartado</option>
                        </select>
                    </div>

                    {{-- Ações de Filtrar e Limpar --}}
                    <div class="admin-filter-actions">
                        <button type="submit" class="admin-btn admin-btn-primary">
                            <x-lucide-filter class="lucid-icon" /> Filtrar
                        </button>
                        <a href="{{ route('admin.outreach.clients.index') }}" class="admin-btn admin-btn-secondary">
                            <x-lucide-brush-cleaning class="lucid-icon" /> Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Coluna 2: Ação em Lote (Disparo de E-mails) --}}
        <div class="outreach-dispatch-col">
            <form id="batchDispatchForm" method="POST" action="{{ route('admin.outreach.dispatch.preview') }}" class="admin-filters">
                @csrf
                <div class="admin-filters-row">
                    <div class="admin-filter-group">
                        <select name="template_id" class="admin-filter-select batch-template-select" required>
                            <option value="">-- Escolher Modelo --</option>
                            @foreach($templates as $tmpl)
                                <option value="{{ $tmpl->id }}">{{ $tmpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="admin-filter-actions">
                        <x-form.button type="submit" icon="send" id="btnBatchSend" disabled>
                            Disparar (<span id="selectedCount">0</span>)
                        </x-form.button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Abas de Status com Pílulas e Contadores --}}
    <div class="content-status-bar">
        <div class="status-tabs-group">
            <a href="{{ route('admin.outreach.clients.index', array_filter(['search' => request('search')])) }}"
               class="status-tab-btn {{ !request('status') ? 'active' : '' }}">
                <x-lucide-circle-check class="lucid-icon" />
                Todos <span class="tab-badge">{{ $counts['all'] ?? 0 }}</span>
            </a>

            <a href="{{ route('admin.outreach.clients.index', array_filter(['status' => 'novo', 'search' => request('search')])) }}"
               class="status-tab-btn {{ request('status') === 'novo' ? 'active' : '' }}">
                <x-lucide-sparkles class="lucid-icon" />
                Novos <span class="tab-badge">{{ $counts['novo'] ?? 0 }}</span>
            </a>

            <a href="{{ route('admin.outreach.clients.index', array_filter(['status' => 'contatado', 'search' => request('search')])) }}"
               class="status-tab-btn {{ request('status') === 'contatado' ? 'active' : '' }}">
                <x-lucide-send class="lucid-icon" />
                Contatados <span class="tab-badge">{{ $counts['contatado'] ?? 0 }}</span>
            </a>

            <a href="{{ route('admin.outreach.clients.index', array_filter(['status' => 'em_conversa', 'search' => request('search')])) }}"
               class="status-tab-btn {{ request('status') === 'em_conversa' ? 'active' : '' }}">
                <x-lucide-message-circle class="lucid-icon" />
                Em Conversa <span class="tab-badge">{{ $counts['em_conversa'] ?? 0 }}</span>
            </a>

            <a href="{{ route('admin.outreach.clients.index', array_filter(['status' => 'fechado', 'search' => request('search')])) }}"
               class="status-tab-btn {{ request('status') === 'fechado' ? 'active' : '' }}">
                <x-lucide-trophy class="lucid-icon" />
                Fechados <span class="tab-badge">{{ $counts['fechado'] ?? 0 }}</span>
            </a>

            <a href="{{ route('admin.outreach.clients.index', array_filter(['status' => 'descartado', 'search' => request('search')])) }}"
               class="status-tab-btn {{ request('status') === 'descartado' ? 'active' : '' }}">
                <x-lucide-archive class="lucid-icon" />
                Descartados <span class="tab-badge">{{ $counts['descartado'] ?? 0 }}</span>
            </a>
        </div>
    </div>

    {{-- Tabela de Contatos --}}
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="selectAllCheckbox" title="Selecionar todos">
                    </th>
                    <th>Contato</th>
                    <th>Empresa / Cargo</th>
                    <th>Status</th>
                    <th>Notas</th>
                    <th>Último Contato</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                <tr>
                    <td>
                        <input type="checkbox" class="client-checkbox" value="{{ $client->id }}" form="batchDispatchForm" name="client_ids[]">
                    </td>
                    <td>
                        <strong>
                            <a href="{{ route('admin.outreach.clients.show', $client->id) }}">{{ $client->name }}</a>
                        </strong>
                        <br>
                        <small class="admin-text-muted">{{ $client->email }}</small>
                    </td>
                    <td>
                        {{ $client->company ?: '-' }}
                        @if($client->role)
                            <br><small class="admin-text-muted">{{ $client->role }}</small>
                        @endif
                    </td>
                    <td>
                        <span class="admin-badge admin-badge-info">{{ ucfirst(str_replace('_', ' ', $client->status)) }}</span>
                    </td>
                    <td>
                        <x-lucide-message-square class="lucid-icon" style="width: 14px; height: 14px; vertical-align: middle;" />
                        <span>{{ $client->notes_count }}</span>
                    </td>
                    <td>{{ $client->last_contacted_at ? $client->last_contacted_at->format('d/m/Y') : 'Nunca' }}</td>
                    <td class="admin-actions">
                        <div>
                            <a href="{{ route('admin.outreach.clients.show', $client->id) }}" class="admin-btn admin-btn-secondary" title="Ver perfil e anotações">
                                <x-lucide-eye class="lucid-icon" />
                            </a>
                            <a href="{{ route('admin.outreach.clients.edit', $client->id) }}" class="admin-btn admin-btn-secondary" title="Editar">
                                <x-lucide-pencil class="lucid-icon" />
                            </a>
                            <form method="POST" action="{{ route('admin.outreach.clients.destroy', $client->id) }}" data-confirm="Remover este contato e todo o seu histórico?" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn-danger" title="Excluir">
                                    <x-lucide-trash-2 class="lucid-icon" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="admin-empty-list">
                            <div>
                                <x-lucide-circle-off class="lucid-icon" />
                            </div>
                            <h3>Nenhum contato encontrado</h3>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-pagination">
        {{ $clients->appends(request()->query())->links() }}
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.client-checkbox');
    const btnSend = document.getElementById('btnBatchSend');
    const countBadge = document.getElementById('selectedCount');

    const updateBatchState = () => {
        const checkedCount = document.querySelectorAll('.client-checkbox:checked').length;
        if (countBadge) countBadge.textContent = checkedCount;
        if (btnSend) btnSend.disabled = (checkedCount === 0);
    };

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBatchState();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBatchState);
    });
});
</script>
@endpush
