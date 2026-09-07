@extends('admin.layout')

@section('header_title', 'Redirecionamentos')
@section('header_subtitle', 'Gerencie regras de redirecionamento de URLs (301 / 302)')

@section('content')

@if(!isset($tableExists) || !$tableExists)
    <div class="admin-card">
        <div class="admin-empty-list" style="padding: 40px;">
            <div><x-lucide-alert-triangle class="lucid-icon" style="color: var(--color-warning);" /></div>
            <h3>Tabela não encontrada</h3>
            <p>A tabela <code>redirect_rules</code> ainda não foi criada no banco de dados. Certifique-se de que as migrações do plugin foram executadas.</p>
        </div>
    </div>
@else
<div class="admin-grid-layout" style="grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">

    <!-- Formulário -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2><x-lucide-plus-circle class="lucid-icon" /> Nova Regra</h2>
        </div>

        <form method="POST" action="{{ route('admin.redirects.store') }}" style="padding: 20px;">
            @csrf

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="old_url" class="admin-label">URL Antiga (Origem)</label>
                <input type="text" name="old_url" id="old_url" value="{{ old('old_url') }}" class="admin-input" placeholder="Ex: /pagina-antiga" required>
                <small class="admin-help-text">Caminho relativo a partir da raiz (ex: <code>/contato-velho</code>)</small>
                @error('old_url') <span class="admin-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label for="new_url" class="admin-label">URL Nova (Destino)</label>
                <input type="text" name="new_url" id="new_url" value="{{ old('new_url') }}" class="admin-input" placeholder="Ex: /novo-contato ou https://site.com" required>
                @error('new_url') <span class="admin-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label for="status_code" class="admin-label">Tipo de Redirecionamento</label>
                <select name="status_code" id="status_code" class="admin-select">
                    <option value="301" {{ old('status_code') == '301' ? 'selected' : '' }}>301 - Permanente (Recomendado para SEO)</option>
                    <option value="302" {{ old('status_code') == '302' ? 'selected' : '' }}>302 - Temporário</option>
                </select>
                @error('status_code') <span class="admin-error">{{ $message }}</span> @enderror
            </div>

            <div class="buttons">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <x-lucide-save class="lucid-icon" /> Salvar Regra
                </button>
            </div>
        </form>
    </div>

    <!-- Tabela -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2><x-lucide-repeat class="lucid-icon" /> Regras Cadastradas</h2>
        </div>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Origem (Old URL)</th>
                        <th>Destino (New URL)</th>
                        <th>Tipo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($redirects as $rule)
                    <tr>
                        <td><code>{{ $rule->old_url }}</code></td>
                        <td><code>{{ $rule->new_url }}</code></td>
                        <td>
                            <span class="admin-badge {{ $rule->status_code == 301 ? 'badge-success' : 'badge-warning' }}">
                                {{ $rule->status_code }}
                            </span>
                        </td>
                        <td class="admin-actions">
                            <div>
                                <form method="POST" action="{{ route('admin.redirects.destroy', $rule->id) }}" data-confirm="Remover esta regra de redirecionamento?" style="display: inline;">
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
                        <td colspan="4">
                            <div class="admin-empty-list">
                                <div><x-lucide-circle-off class="lucid-icon" /></div>
                                <h3>Nenhuma regra cadastrada</h3>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">
            {{ $redirects->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endif
@endsection
