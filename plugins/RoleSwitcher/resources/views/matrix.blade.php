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
        <strong>ATENÇÃO</strong> Esta é uma funcionalidade com privilégios sensíveis e deve ser utilizada com cautela e apenas em situações específicas.
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

        <div class="buttons">
            <button type="submit" class="admin-btn admin-btn-primary">
                <x-lucide-save class="lucid-icon" /> Salvar Matriz
            </button>
        </div>
    </form>
</div>
@endsection
