{{-- <div class="current-role">
    <x-lucide-arrow-left-right class="lucid-icon" />
    Perfil atual: {{ $currentRole }}
</div> --}}
<div class="current-role" style="display: flex. align-items: center; margin-right: 1rem;">
    <x-lucide-arrow-left-right class="lucid-icon" />
    <span>Perfil: <strong>{{ $currentRole }}</strong></span>

    <form method="POST" action="{{ route('admin.role-switcher.reset') }}" class="current-role-form" style="display: inline;">
        @csrf
        <button type="submit" class="admin-btn admin-btn-danger current-role-btn" title="Voltar ao papel original" style="padding: 3px 8px; margin-left: 1rem;">
            <x-lucide-x class="lucid-icon" /> Sair
        </button>
    </form>
</div>
