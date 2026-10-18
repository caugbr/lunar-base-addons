<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-hash class="lucid-icon" /> Discord Notifier
        </h3>
        <p>
            Envie alertas, relatórios e eventos do seu site diretamente para canais do Discord via Webhooks.
        </p>
    </header>

    <h4>1. Como criar a URL de Webhook no Discord</h4>
    <ol style="margin-left: 1.25rem; line-height: 1.6;">
        <li>Abra o seu servidor no Discord (no app ou navegador).</li>
        <li>Passe o mouse sobre o canal onde deseja receber as mensagens (ex: <code>#alertas</code>) e clique na engrenagem <strong>"Editar Canal"</strong>.</li>
        <li>Vá na aba <strong>"Integrações"</strong> e clique em <strong>"Criar Webhook"</strong> (ou "Ver Webhooks").</li>
        <li>Dê um nome e clique em <strong>"Copiar URL do Webhook"</strong>.</li>
        <li>Cole essa URL no formulário ao lado e salve.</li>
    </ol>

    <h4>2. Como usar no código PHP</h4>
    <p>Para enviar mensagens em texto comum (com suporte ao Markdown do Discord como negrito com <code>**</code>):</p>
    <div class="code">
discordNotify("**Nova Venda Realizada!**\nPedido #1052 aprovado.");
    </div>

    <p style="margin-top: 1rem;">Você também pode enviar cards coloridos (Embeds):</p>
    <div class="code">
discordNotify('', null, [
    'title'       => 'Erro Crítico no Sistema',
    'description' => 'Falha de conexão com a API de pagamento.',
    'color'       => hexdec('EF4444'), // Vermelho
]);
    </div>

    <x-configurable-plugin-values plugin="DiscordNotifier" />
</div>
