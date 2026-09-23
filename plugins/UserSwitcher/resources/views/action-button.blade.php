@if(auth()->id() !== $user->id && !session()->has('impersonate_original_admin_id'))
    <form method="POST" action="{{ route('admin.user-switcher.start', $user->id) }}" style="display: inline-block;" data-confirm="Deseja realmente assumir a sessão deste usuário?">
        @csrf
        <button type="submit"
                class="admin-btn admin-btn-sm admin-btn-secondary"
                title="Acessar painel como {{ $user->name }}">
            <x-lucide-user-check class="lucid-icon" />
        </button>
    </form>
@endif
