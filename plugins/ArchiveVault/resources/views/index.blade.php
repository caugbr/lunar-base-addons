@extends('admin.layout')

@section('header_title', 'Arquivo Morto (Cofre)')
@section('header_subtitle', 'Auditoria forense de itens excluídos definitivamente do sistema')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-archive class="lucid-icon" /> Registros Preservados</h2>
        <a href="{{ route('admin.tools.index') }}" class="admin-btn admin-btn-secondary">
            <x-lucide-arrow-left class="lucid-icon" /> Voltar para Ferramentas
        </a>
    </div>

    @if(!$tableAvailable)
        {{-- ALERTA QUANDO A TABELA AINDA NÃO FOI CRIADA --}}
        <div class="admin-empty-list" style="padding: 3rem 1.5rem; text-align: center;">
            <div style="color: #f59e0b; margin-bottom: 1rem;">
                <x-lucide-alert-triangle style="width: 48px; height: 48px;" />
            </div>
            <h3 style="margin-bottom: 0.5rem; color: #1e293b;">Migração Pendente</h3>
            <p style="color: #64748b; max-width: 480px; margin: 0 auto 1.5rem auto;">
                A tabela do cofre ainda não foi criada no seu banco de dados. Para ativar esta funcionalidade, execute o comando de migração no seu terminal:
            </p>
            <div style="display: inline-block; background: #0f172a; color: #38bdf8; font-family: monospace; padding: 10px 20px; border-radius: 6px; font-size: 14px;">
                php artisan migrate
            </div>
        </div>
    @else
        <!-- Abas de Filtro -->
        <div class="content-status-bar">
            <div class="status-tabs-group">
                <a href="{{ route('admin.archive_vault.index') }}"
                    class="status-tab-btn {{ !request('type') ? 'active' : '' }}">
                    Todos <span class="tab-badge">{{ $counts['all'] }}</span>
                </a>

                <a href="{{ route('admin.archive_vault.index', ['type' => 'post']) }}"
                    class="status-tab-btn {{ request('type') === 'post' ? 'active' : '' }}">
                    Posts <span class="tab-badge">{{ $counts['posts'] }}</span>
                </a>

                <a href="{{ route('admin.archive_vault.index', ['type' => 'page']) }}"
                    class="status-tab-btn {{ request('type') === 'page' ? 'active' : '' }}">
                    Páginas <span class="tab-badge">{{ $counts['pages'] }}</span>
                </a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Título Original</th>
                        <th>Tipo</th>
                        <th>Criado em</th>
                        <th>Expurgado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $rec)
                    <tr>
                        <td>
                            <strong>{{ $rec->title }}</strong>
                            <br><small class="admin-text-muted">Slug: {{ $rec->slug }} (ID original: #{{ $rec->original_id }})</small>
                        </td>
                        <td>
                            <span class="admin-badge {{ $rec->type_name === 'Post' ? 'admin-badge-active' : 'admin-badge-suspended' }}">
                                {{ $rec->type_name }}
                            </span>
                        </td>
                        <td>{{ $rec->original_created_at ? $rec->original_created_at->format('d/m/Y H:i') : '—' }}</td>
                        <td><strong style="color: #dc2626;">{{ $rec->purged_at->format('d/m/Y H:i') }}</strong></td>
                        <td class="admin-actions">
                            <div>
                                <a href="{{ route('admin.archive_vault.show', $rec->id) }}" class="admin-btn admin-btn-secondary" title="Inspecionar Conteúdo Original">
                                    <x-lucide-eye class="lucid-icon" />
                                </a>

                                <form method="POST" action="{{ route('admin.archive_vault.restore', $rec->id) }}" data-confirm="Ressuscitar este item? Ele voltará para a lista como Rascunho.">
                                    @csrf
                                    <button type="submit" class="admin-btn admin-btn-primary" title="Ressuscitar como Rascunho">
                                        <x-lucide-rotate-ccw class="lucid-icon" />
                                    </button>
                                </form>

                                @if(auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('admin.archive_vault.destroy', $rec->id) }}" data-confirm="Apagar definitivamente do cofre? Não haverá mais nenhuma forma de recuperar esse dado!">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="admin-btn admin-btn-danger" title="Expurgar do cofre">
                                        <x-lucide-trash-2 class="lucid-icon" />
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <div class="admin-empty-list">
                                <x-lucide-shield-check class="lucid-icon" />
                                <h3>O cofre está vazio</h3>
                                <p>Nenhum post ou página foi excluído definitivamente da lixeira ainda.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
        <div class="admin-pagination">
            {{ $records->links() }}
        </div>
        @endif
    @endif
</div>
@endsection

@push('styles')
<style>
/* ==========================================================================
   BARRA DE ABAS DE FILTRO (Lunar Base UI)
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

/* Botões em formato de pílula */
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

/* Aba Ativa */
.status-tab-btn.active {
    background: #0f172a;
    border-color: #0f172a;
    color: #ffffff;
}

/* Badge com a contagem numérica */
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
</style>
@endpush
