<div class="help-content">
    <div class="help-header">
        <h3>
            <x-lucide-help-circle class="lucid-icon" />
            Novo Contato
        </h3>
        <p>Cadastre um lead ou futuro cliente para iniciar o acompanhamento e futuras prospecções.</p>
    </div>

    <div class="help-body">
        <ul class="help-list">
            <li>
                <strong>Campos Obrigatórios:</strong>
                O preenchimento do <em>Nome</em> e do <em>E-mail</em> é essencial para identificação e para a entrega correta das mensagens.
            </li>
            <li>
                <strong>Empresa e Cargo:</strong>
                Preencha sempre que possível. Essas informações podem ser inseridas automaticamente nos e-mails de abordagem através das variáveis <code>@{{empresa}}</code> e <code>@{{cargo}}</code>.
            </li>
            <li>
                <strong>Telefone e Website:</strong>
                Dados auxiliares para enriquecer a ficha do cliente e permitir abordagens complementares via WhatsApp ou conferência de links institucionais.
            </li>
            <li>
                <strong>Status Inicial:</strong>
                Por padrão, novos cadastros entram como <em>Novo</em>, mas você pode classificá-los em etapas avançadas caso a conversa já tenha começado antes do cadastro.
            </li>
            <li>
                <strong>Anotação Inicial (Opcional):</strong>
                Permite registrar o contexto inicial do lead (onde o conheceu, indicação, observações preliminares). O texto será adicionado automaticamente à Linha do Tempo após salvar.
            </li>
        </ul>
    </div>

    <div class="help-footer">
        <div>
            <x-lucide-info class="lucid-icon" />
            <div>
                Ao salvar, você será redirecionado para a ficha do contato para iniciar o registro de atividades.
            </div>
        </div>
    </div>
</div>
