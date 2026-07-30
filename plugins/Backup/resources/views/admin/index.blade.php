@extends('admin.layout')

@section('header_title', 'Backups do Sistema')
@section('header_subtitle', 'Gere, baixe, importe e restaure cópias de segurança do Lunar Base')

@section('content')
@once
@push('styles')
<link rel="stylesheet" href="{{ asset('plugins/backup/css/backup-admin.css') }}">
@endpush
@endonce

<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-archive class="lucid-icon" /> Backups Existentes</h2>
        <div style="display: flex; gap: 0.5rem;">
            {{-- Botão Importar ZIP --}}
            <button type="button"
                    class="admin-btn admin-btn-secondary"
                    onclick="window.dispatchEvent(new CustomEvent('modal-open', { detail: { id: 'modal-import-backup' } }))">
                <x-lucide-upload class="lucid-icon" /> Importar Backup (.zip)
            </button>

            {{-- Botão Criar Backup --}}
            <button type="button"
                    class="admin-btn admin-btn-primary"
                    onclick="window.dispatchEvent(new CustomEvent('modal-open', { detail: { id: 'modal-create-backup' } }))">
                <x-lucide-plus class="lucid-icon" /> Criar Backup
            </button>
        </div>
    </div>

    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Arquivo</th>
                    <th>Tamanho</th>
                    <th>Data da Criação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($backups as $backup)
                <tr>
                    <td>
                        <strong>{{ $backup['filename'] }}</strong>
                    </td>
                    <td><code>{{ $backup['size_formatted'] }}</code></td>
                    <td>{{ $backup['created_at'] }}</td>
                    <td class="admin-actions" style="justify-content: flex-end;">
                        <div>
                            {{-- Baixar --}}
                            <a href="{{ route('admin.backups.download', $backup['filename']) }}"
                               class="admin-btn admin-btn-secondary"
                               title="Baixar arquivo ZIP">
                                <x-lucide-download class="lucid-icon" />
                            </a>

                            {{-- Restaurar --}}
                            <button type="button"
                                    class="admin-btn admin-btn-secondary"
                                    title="Restaurar este backup"
                                    onclick="triggerBackupRestore('{{ $backup['filename'] }}')">
                                <x-lucide-rotate-ccw class="lucid-icon" />
                            </button>

                            {{-- Excluir --}}
                            <form method="POST" action="{{ route('admin.backups.destroy', $backup['filename']) }}" style="display: inline;" data-confirm="Remover este arquivo de backup?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn-danger" title="Excluir backup">
                                    <x-lucide-trash-2 class="lucid-icon" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="admin-text-center admin-text-muted" style="padding: 2rem;">
                        Nenhum backup gerado ou importado ainda. Clique no botão acima para criar ou enviar um arquivo!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Modal de Importação (Upload de .zip) --}}
<x-modal id="modal-import-backup" title="Importar Arquivo de Backup" size="md">
    <form method="POST" action="{{ route('admin.backups.import') }}" enctype="multipart/form-data">
        @csrf
        <p style="margin-bottom: 1rem; color: var(--color-text-muted);">Envie um arquivo <code>.zip</code> de backup gerado previamente pelo Lunar Base:</p>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="backup_file">Arquivo de Backup (.zip) *</label>
            <x-upload-area name="backup_file" id="backup_file" accept=".zip" />
            <small class="form-help">Tamanho máximo permitido: 500MB.</small>
        </div>

        <div class="buttons" style="justify-content: flex-end;">
            <button type="submit" class="admin-btn admin-btn-primary">
                <x-lucide-upload class="lucid-icon" /> Fazer Upload e Adicionar à Lista
            </button>
        </div>
    </form>
</x-modal>

{{-- Modal de Criação de Backup --}}
<x-modal id="modal-create-backup" title="Criar Novo Backup" size="md">
    <form method="POST" action="{{ route('admin.backups.store') }}">
        @csrf
        <p style="margin-bottom: 1rem; color: var(--color-text-muted);">Selecione quais itens você deseja incluir no pacote .zip:</p>

        <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.5rem;">
            <label class="checkbox-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="include_db" value="1" checked>
                <span><strong>Banco de Dados</strong> (SQL/SQLite)</span>
            </label>

            <label class="checkbox-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="include_media" value="1" checked>
                <span><strong>Mídias e Uploads</strong> (storage/app/public)</span>
            </label>

            <label class="checkbox-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="include_plugins" value="1" checked>
                <span><strong>Plugins Customizados</strong> (pasta plugins/)</span>
            </label>

            <label class="checkbox-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="include_themes" value="1" checked>
                <span><strong>Temas Visuais</strong> (pasta themes/)</span>
            </label>
        </div>

        <div class="buttons" style="justify-content: flex-end;">
            <button type="submit" class="admin-btn admin-btn-primary">
                <x-lucide-archive class="lucid-icon" /> Iniciar Backup
            </button>
        </div>
    </form>
</x-modal>

{{-- Backdrop de Restauração --}}
<div id="backup-restore-backdrop" class="backup-overlay" style="display: none !important;">
    <div class="backup-spinner"></div>
    <h2 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; color: #fff;">Restaurando o Sistema...</h2>
    <p style="color: #94a3b8; font-size: 0.95rem; max-width: 420px; line-height: 1.5;">
        Substituindo o banco de dados, mídias e extensões. Por favor, <strong>não feche nem recarregue a página</strong>.
    </p>
</div>

@push('scripts')
<script>
    async function triggerBackupRestore(filename) {
        const confirmed = await Dialog.confirm(`ATENÇÃO: Restaurar o backup '${filename}' substituirá os dados e mídias atuais do site. Deseja continuar?`);
        if (!confirmed) {
            return;
        }

        const backdrop = document.getElementById('backup-restore-backdrop');
        backdrop.style.setProperty('display', 'flex', 'important');

        const preventUnload = (e) => {
            e.preventDefault();
            e.returnValue = 'Restauração em andamento. Não feche a página!';
            return e.returnValue;
        };
        window.addEventListener('beforeunload', preventUnload);

        try {
            const response = await fetch(`/admin/backups/restore/${filename}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });

            const data = await response.json();
            window.removeEventListener('beforeunload', preventUnload);

            if (response.ok && data.success) {
                Dialog.alert('Sistema restaurado com sucesso!');
                window.location.reload();
            } else {
                Dialog.alert('Erro na restauração: ' + (data.message || 'Falha ao aplicar arquivo.'));
                backdrop.style.setProperty('display', 'none', 'important');
            }
        } catch (error) {
            window.removeEventListener('beforeunload', preventUnload);
            Dialog.alert('Erro de conexão durante a restauração.');
            backdrop.style.setProperty('display', 'none', 'important');
        }
    }
</script>
@endpush
@endsection
