@if(session()->has('impersonate_original_admin_id'))
<div class="current-role" style="display: flex. align-items: center; margin-right: 1rem;">
    <x-lucide-user-check class="lucid-icon" />
    <span>Usuário: <strong>{{ auth()->user()->name }}</strong></span>

    <form method="POST" action="{{ route('admin.user-switcher.stop') }}" class="current-role-form" style="display: inline;">
        @csrf
        <button type="submit" class="admin-btn admin-btn-danger current-role-btn" title="Voltar ao papel original" style="padding: 3px 8px; margin-left: 1rem;">
            <x-lucide-x class="lucid-icon" /> Sair
        </button>
    </form>
</div>
@endif
