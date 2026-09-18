<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-arrow-left-right class="lucid-icon" /> Simulação de Papel</h2>
    </div>


    @if($currentSwitchedRole)
    <p class="text-muted">
    Perfil atual: {{ ucfirst($currentSwitchedRole) }}
    </p>

    <form method="POST" action="{{ route('admin.role-switcher.reset') }}">
        @csrf
        <button type="submit" class="admin-btn admin-btn-danger">
            <x-lucide-log-out class="lucid-icon" /> Voltar ao Papel Real ({{ ucfirst($realRole) }})
        </button>
    </form>
    @else
    <p class="text-muted">
        Selecione um perfil para alternar temporariamente suas permissões e telas na administração sem alterar seu cadastro original.
    </p>

    <div class="admin-form-row">
        <form method="POST" action="{{ route('admin.role-switcher.switch') }}" class="admin-filters-row">
            @csrf
            <div class="admin-filter-group">
                <label for="target_role">Operar como:</label>
                <select name="target_role" id="target_role" class="admin-filter-select">
                    @foreach($availableRoles as $roleKey)
                        <option value="{{ $roleKey }}" {{ $currentSwitchedRole === $roleKey ? 'selected' : '' }}>
                            {{ $allRoles[$roleKey]['name'] ?? ucfirst($roleKey) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="admin-filter-actions">
                <button type="submit" class="admin-btn admin-btn-secondary">
                    <x-lucide-repeat class="lucid-icon" /> Alternar Papel
                </button>
            </div>
        </form>
    </div>
    @endif
</div>
