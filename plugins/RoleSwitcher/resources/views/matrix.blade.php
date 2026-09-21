@extends('admin.layout')

@section('header_title', 'Alternância de Papéis')
@section('header_subtitle', 'Defina quais papéis podem simular outros perfis')

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-arrow-left-right class="lucid-icon" /> Matriz de Permissões de Papel</h2>
    </div>

    <p>
        Esta matriz define quais papéis têm autorização para simular outros perfis de usuário. Usuários com papéis de origem habilitados poderão alternar temporariamente sua visualização e permissões diretamente na página de perfil, sem alterar seus cadastros originais.
    </p>
    <p>
        <strong>ATENÇÃO:</strong> Esta é uma funcionalidade com privilégios sensíveis e deve ser utilizada com cautela e apenas em situações específicas.
    </p>

    <form method="POST" action="{{ route('admin.role-switcher.matrix.update') }}">
        @csrf

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Papel Original</th>
                    <th>Pode assumir o papel de:</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $sourceKey => $sourceData)
                <tr>
                    <td class="font-medium">
                        <strong>{{ $sourceData['name'] ?? ucfirst($sourceKey) }}</strong>
                        <div class="text-muted">Código: {{ $sourceKey }}</div>
                    </td>
                    <td>
                        <div class="checkbox-group">
                            @foreach($roles as $targetKey => $targetData)
                                @if($sourceKey !== $targetKey)
                                    @php
                                        $isChecked = in_array($targetKey, $matrix[$sourceKey] ?? []);
                                    @endphp
                                    <label class="checkbox-label">
                                        <input type="checkbox"
                                               name="matrix[{{ $sourceKey }}][]"
                                               value="{{ $targetKey }}"
                                               {{ $isChecked ? 'checked' : '' }}>
                                        <span>{{ $targetData['name'] ?? ucfirst($targetKey) }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Seção de URLs de Redirecionamento (aparece após salvar com itens ticados) -->
        @if(!empty($activeTargetRoles))
        <div class="redirects-section">
            <h3 class="redirects-title">
                <x-lucide-link class="lucid-icon" /> Redirecionamentos Personalizados
            </h3>
            <p class="text-muted">
                Defina a URL para onde o usuário deve ser enviado ao assumir cada papel. Deixe em branco para usar o padrão (<code>/admin/profile</code>).
            </p>

            <table class="admin-table redirects-table">
                <thead>
                    <tr>
                        <th>Papel Ativado</th>
                        <th>URL de Destino</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activeTargetRoles as $roleKey)
                    <tr>
                        <td>
                            <strong>{{ $roles[$roleKey]['name'] ?? ucfirst($roleKey) }}</strong>
                            <div class="text-muted">Código: {{ $roleKey }}</div>
                        </td>
                        <td>
                            <input type="text"
                                   name="redirects[{{ $roleKey }}]"
                                   value="{{ $redirects[$roleKey] ?? '' }}"
                                   placeholder="/admin/profile"
                                   class="redirect-url-input">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="buttons">
            <button type="submit" class="admin-btn admin-btn-primary">
                <x-lucide-save class="lucid-icon" /> Salvar Matriz
            </button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .redirects-section { margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border, #e2e8f0); }
    .redirects-title { font-size: 1.1rem; font-weight: 600; color: var(--color-text, #1f2937); margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 0.5rem; }
    .redirects-table { max-width: 650px; margin-top: 1rem; }
    .redirect-url-input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid var(--color-border, #d1d5db); border-radius: 0.375rem; font-size: 0.875rem; box-sizing: border-box; }
    .redirect-url-input:focus { outline: none; border-color: var(--color-primary, #6366f1); }
</style>
@endpush
