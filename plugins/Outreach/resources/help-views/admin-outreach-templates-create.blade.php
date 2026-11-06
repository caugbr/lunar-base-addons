<div class="help-content">
    <div class="help-header">
        <h3>
            <x-lucide-help-circle class="lucid-icon" />
            Criar Modelo de E-mail
        </h3>
        <p>Redija uma nova mensagem rica com tags dinâmicas de substituição para envios personalizados.</p>
    </div>

    <div class="help-body">
        <ul class="help-list">
            <li>
                <strong>Identificação do Modelo:</strong>
                Nome de controle interno visível apenas no painel administrativo para organizar seus modelos.
            </li>
            <li>
                <strong>Assunto Personalizado:</strong>
                Você pode utilizar tags no assunto (ex: <code>@{{primeiro_nome}}</code> ou <code>@{{empresa}}</code>) para aumentar a taxa de abertura do e-mail.
            </li>
            <li>
                <strong>Editor Tiptap Formatado:</strong>
                Escreva o corpo da mensagem com negrito, itálico, links e listas. O editor está configurado em modo seguro para garantir que a formatação não quebre em clientes de e-mail corporativos.
            </li>
            <li>
                <strong>Caixa Lateral de Variáveis:</strong>
                Clique sobre qualquer tag da lista lateral (ex: <code>@{{nome}}</code>, <code>@{{site}}</code>) para copiar o código para a área de transferência e colar no texto.
            </li>
        </ul>
    </div>

    <div class="help-footer">
        <div>
            <x-lucide-info class="lucid-icon" />
            <div>
                Após salvar, o modelo estará disponível imediatamente na seleção de envio da lista de contatos.
            </div>
        </div>
    </div>
</div>
