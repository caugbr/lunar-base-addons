<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-message-circle class="lucid-icon" /> Botão Flutuante do WhatsApp
        </h3>
        <p>
            Permita que os visitantes iniciem conversas com sua equipe no WhatsApp com 1 clique.
        </p>
    </header>

    <h4>Injeção Automática via Hook</h4>
    <p>
        O botão é renderizado automaticamente através do hook de layout <code>main.after_content</code>, sem a necessidade de editar nenhum arquivo de tema.
    </p>

    <h4>Horário Comercial & Comportamento</h4>
    <p>
        Você pode limitar o atendimento para dias e faixas de horários específicos. Fora desse período, você escolhe se o botão <strong>some da tela</strong> ou se permanece <strong>visível porém desabilitado (cinza)</strong>, informando o horário aos visitantes.
    </p>

    <h4>Tags Dinâmicas na Mensagem</h4>
    <p>Utilize <code>{url}</code> no texto da mensagem padrão para que o cliente envie automaticamente o link exato da página que estava visitando ao abrir a conversa.</p>

    <x-configurable-plugin-values plugin="WhatsAppButton" />
</div>
