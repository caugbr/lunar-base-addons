<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-send class="lucid-icon" /> Telegram Notifier
        </h3>
        <p>
            Dispare alertas e notificações formatadas em HTML do seu site diretamente para o Telegram.
        </p>
    </header>

    <h4>1. Como obter o Token do Bot</h4>
    <ol style="margin-left: 1.25rem; line-height: 1.6;">
        <li>Abra o Telegram e busque pelo bot oficial <strong>@BotFather</strong>.</li>
        <li>Envie o comando <code>/newbot</code> e siga as instruções para definir nome e username.</li>
        <li>Copie o token gerado (algo como <code>123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11</code>).</li>
    </ol>

    <h4>2. Como obter o Chat ID</h4>
    <ul style="margin-left: 1.25rem; line-height: 1.6;">
        <li><strong>Para receber no privado:</strong> Abra uma conversa com o seu bot, clique em <strong>Começar</strong> (ou envie <code>/start</code>), depois busque pelo bot <code>@userinfobot</code> para descobrir o seu ID numérico.</li>
        <li><strong>Para receber em grupo:</strong> Adicione o seu bot ao grupo e envie uma mensagem qualquer. O ID do grupo começará com sinal de menos (ex: <code>-100xxxxxxxxx</code>).</li>
    </ul>

    <h4>3. Como usar no código PHP</h4>
    <p>Em qualquer Controller, Model, Command ou Listener do sistema, basta chamar o helper:</p>
    <div class="code">
telegramNotify("<b>Nova Venda Realizada!</b>\nPedido: #1052\nValor: R$ 150,00");
    </div>

    <x-configurable-plugin-values plugin="TelegramNotifier" />
</div>
