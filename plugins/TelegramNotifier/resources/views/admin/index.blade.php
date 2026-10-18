@extends('admin.layout')

@section('header_title', 'Telegram Notifier')
@section('header_subtitle', 'Configure notificações automáticas do site direto no seu Telegram')

@section('content')

{{-- Card de Configuração --}}
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-bot class="lucid-icon" /> Credenciais do Bot</h2>
    </div>

    <form method="POST" action="{{ route('admin.telegram-notifier.update') }}">
        @csrf

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label for="telegram_bot_token">Token da API do Bot *</label>
            <input type="text"
                    name="telegram_bot_token"
                    id="telegram_bot_token"
                    class="form-input"
                    value="{{ old('telegram_bot_token', $botToken) }}"
                    placeholder="Ex: 123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ"
                    required>
            <small class="form-help">Token fornecido pelo <strong>@BotFather</strong> ao criar seu bot.</small>
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="telegram_chat_id">Chat ID (Destinatário) *</label>
            <input type="text"
                    name="telegram_chat_id"
                    id="telegram_chat_id"
                    class="form-input"
                    value="{{ old('telegram_chat_id', $chatId) }}"
                    placeholder="Ex: 987654321 ou -1001234567890"
                    required>
            <small class="form-help">ID do usuário, grupo ou canal. Para grupos, adicione o bot ao grupo antes de testar.</small>
        </div>

        <div class="buttons" style="display: flex; justify-content: flex-end;">
            <button type="submit" class="admin-btn admin-btn-primary">
                <x-lucide-save class="lucid-icon" /> Salvar Configurações
            </button>
        </div>
    </form>
</div>

{{-- Card de Teste de Conexão --}}
<div class="admin-card">
    <div class="admin-card-header">
        <h3><x-lucide-send class="lucid-icon" /> Teste de Conexão</h3>
    </div>

    <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-bottom: 1.25rem; line-height: 1.5;">
        Clique no botão abaixo para disparar uma mensagem de teste para o Telegram usando as credenciais salvas acima.
    </p>

    <form method="POST" action="{{ route('admin.telegram-notifier.test') }}">
        @csrf
        <div class="buttons" style="justify-content: flex-start;">
            <button type="submit" class="admin-btn admin-btn-secondary" {{ empty($botToken) || empty($chatId) ? 'disabled' : '' }}>
                <x-lucide-message-square class="lucid-icon" /> Enviar Mensagem de Teste
            </button>
        </div>
    </form>
</div>

@endsection
