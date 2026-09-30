<div class="help-content">
    <div class="help-header">
        <h3>
            <x-lucide-mouse-pointer-click class="lucid-icon" />
            Eventos & Ações Rastreadas
        </h3>
        <p>Acompanhamento de cliques, interações importantes e metas de conversão disparadas pelos usuários no site.</p>
    </div>

    <div class="help-body">
        <ul class="help-list">
            <li>
                <strong>Como funciona o rastreamento:</strong>
                O sistema captura automaticamente qualquer clique em elementos HTML que possuam o atributo <code>data-track-event</code> no tema público.
            </li>
            <li>
                <strong>Como rastrear novos botões:</strong>
                Basta adicionar os atributos ao elemento HTML desejado em suas views Blade:<br>
                <code>&lt;a href="..." data-track-event="Clique WhatsApp" data-track-category="Contato"&gt;Fale Conosco&lt;/a&gt;</code>
            </li>
            <li>
                <strong>Nome vs. Categoria:</strong>
                O <em>Nome</em> define a ação específica (ex: "Download PDF", "Assinar Newsletter"), enquanto a <em>Categoria</em> serve para agrupar metas semelhantes (ex: "Conversão", "Botão", "Lead").
            </li>
            <li>
                <strong>Garantia de Envio (sendBeacon):</strong>
                Mesmo que o usuário clique em um link que o redirecione imediatamente para outro site (como o WhatsApp), a requisição é transmitida em segundo plano sem ser cancelada pelo navegador.
            </li>
        </ul>
    </div>

    <div class="help-footer">
        <div>
            <x-lucide-info class="lucid-icon" />
            <div>
                O registro de eventos utiliza o mesmo hash diário LGPD das páginas, permitindo auditar o volume de ações por usuários únicos sem violar a privacidade.
            </div>
        </div>
    </div>
</div>
