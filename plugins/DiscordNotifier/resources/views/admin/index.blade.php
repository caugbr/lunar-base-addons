@extends('admin.layout')

@section('header_title', 'Discord Notifier')
@section('header_subtitle', 'Configure notificações automáticas do site direto em canais do Discord')

@section('content')
{{-- Card de Configuração --}}
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-hash class="lucid-icon" /> Configuração do Webhook</h2>
    </div>

    <form method="POST" action="{{ route('admin.discord-notifier.update') }}">
        @csrf

        <div class="form-group" style="margin-bottom: 1.25rem;">
            <label for="discord_webhook_url">URL do Webhook do Discord *</label>
            <input type="url"
                    name="discord_webhook_url"
                    id="discord_webhook_url"
                    class="form-input"
                    value="{{ old('discord_webhook_url', $webhookUrl) }}"
                    placeholder="https://discord.com/api/webhooks/..."
                    required>
            <small class="form-help">Copie a URL criada em: <strong>Editar Canal > Integrações > Webhooks > Novo Webhook</strong>.</small>
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="discord_bot_name">Nome de Exibição do Bot</label>
            <input type="text"
                    name="discord_bot_name"
                    id="discord_bot_name"
                    class="form-input"
                    value="{{ old('discord_bot_name', $botName) }}"
                    placeholder="Ex: Alertas Lunar Base">
            <small class="form-help">O nome que aparecerá no Discord como autor da mensagem (opcional).</small>
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
        <h3><x-lucide-send class="lucid-icon" /> Teste de Envio</h3>
    </div>

    <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-bottom: 1.25rem; line-height: 1.5;">
        Clique no botão abaixo para disparar um Embed de teste para o canal do Discord configurado acima.
    </p>

    <form method="POST" action="{{ route('admin.discord-notifier.test') }}">
        @csrf
        <div class="buttons" style="justify-content: flex-start;">
            <button type="submit" class="admin-btn admin-btn-secondary" {{ empty($webhookUrl) ? 'disabled' : '' }}>
                <x-lucide-message-square class="lucid-icon" /> Enviar Mensagem de Teste
            </button>
        </div>
    </form>
</div>
@endsection
