@extends('admin.layout')

@section('header_title', 'Erros 404 (Páginas Não Encontradas)')
@section('header_subtitle', 'Monitore URLs quebradas acessadas por visitantes e converta-as em redirecionamentos')

@section('content')
<x-admin-alert />

@if(!isset($tableExists) || !$tableExists)
    <div class="admin-card">
        <div class="admin-empty-list" style="padding: 40px;">
            <div><x-lucide-alert-triangle class="lucid-icon" style="color: var(--color-warning);" /></div>
            <h3>Tabela não encontrada</h3>
            <p>A tabela <code>not_found_logs</code> ainda não foi criada no banco de dados. Certifique-se de que as migrações do plugin foram executadas.</p>
        </div>
    </div>
@else
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-alert-triangle class="lucid-icon" /> Tentativas de Acesso Inválidas</h2>
    </div>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>URL Tentada</th>
                    <th>Hits (Acessos)</th>
                    <th>Referrer (Origem)</th>
                    <th>Último Acesso</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td><code>{{ $log->url }}</code></td>
                    <td><span class="admin-badge badge-warning">{{ $log->hits }}</span></td>
                    <td>
                        @if($log->referrer)
                            <a href="{{ $log->referrer }}" target="_blank" title="{{ $log->referrer }}">
                                {{ Str::limit($log->referrer, 40) }}
                            </a>
                        @else
                            <span class="text-muted">Acesso direto / Desconhecido</span>
                        @endif
                    </td>
                    <td>{{ $log->last_accessed_at ? $log->last_accessed_at->format('d/m/Y H:i') : '-' }}</td>
                    <td class="admin-actions">
                        <div>
                            <button type="button" class="admin-btn admin-btn-primary" onclick="openConvertModal('{{ $log->id }}', '{{ $log->url }}')" title="Converter em Redirecionamento">
                                <x-lucide-arrow-right-left class="lucid-icon" />
                            </button>

                            <form method="POST" action="{{ route('admin.redirects.404.destroy', $log->id) }}" data-confirm="Remover este registro de erro 404?" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn-danger" title="Excluir Log">
                                    <x-lucide-trash-2 class="lucid-icon" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="admin-empty-list">
                            <div><x-lucide-check-circle class="lucid-icon" /></div>
                            <h3>Nenhum erro 404 registrado!</h3>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-pagination">
        {{ $logs->appends(request()->query())->links() . '' }}
    </div>
</div>

<!-- Modal para converter 404 em Redirecionamento -->
<x-modal id="convertModal" title="Converter 404 em Redirecionamento" size="md" :showFooter="true">
    <form id="convertForm" method="POST">
        @csrf
        <div class="form-group" style="margin-bottom: 16px;">
            <label class="admin-label">URL de Origem (404)</label>
            <input type="text" id="modal_old_url" class="admin-input" disabled style="background: var(--color-bg-subtle);">
        </div>

        <div class="form-group">
            <label for="modal_new_url" class="admin-label">Nova URL de Destino</label>
            <input type="text" name="new_url" id="modal_new_url" class="admin-input" placeholder="Ex: /pagina-correta ou https://..." required>
            <small class="admin-help-text">Ao salvar, a regra 301 será criada e este registro 404 será apagado.</small>
        </div>

        <x-slot name="footer">
            <button type="submit" class="admin-btn admin-btn-primary">
                <x-lucide-save class="lucid-icon" /> Criar Redirecionamento
            </button>
        </x-slot>
    </form>
</x-modal>

<script>
function openConvertModal(id, url) {
    document.getElementById('modal_old_url').value = url;
    document.getElementById('modal_new_url').value = '';
    document.getElementById('convertForm').action = `/admin/redirects/404/${id}/convert`;

    window.dispatchEvent(new CustomEvent('modal-open', { detail: { id: 'convertModal' } }));
}
</script>
@endif
@endsection
