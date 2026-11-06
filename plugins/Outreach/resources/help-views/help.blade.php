@push('styles')
<style>
    .help-variables-table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0 1.5rem;
        font-size: 0.875rem;
    }

    .help-variables-table th,
    .help-variables-table td {
        padding: 0.625rem 0.75rem;
        border: 1px solid var(--color-border, #e9ecef);
        text-align: left;
    }

    .help-variables-table th {
        background-color: var(--color-bg-subtle, #f8f9fa);
        font-weight: 600;
        color: var(--color-text, #212529);
    }

    .help-variables-table code {
        font-family: monospace;
        color: var(--color-primary, #0056b3);
        background-color: var(--color-bg-subtle, #f1f5f9);
        padding: 2px 6px;
        border-radius: 4px;
    }

    .help-callout-info {
        background-color: #eff6ff;
        border-left: 4px solid var(--color-primary, #0056b3);
        padding: 0.875rem 1rem;
        border-radius: 0 6px 6px 0;
        margin: 1rem 0;
        font-size: 0.875rem;
        color: #1e40af;
        line-height: 1.5;
    }
</style>
@endpush

<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-send class="lucid-icon" />
            Outreach & Mini-CRM
        </h3>
        <p>
            Sistema integrado para gestão de leads, registro de notas/histórico comercial e disparo escalonado de e-mails de prospecção personalizados via SMTP (Brevo).
        </p>
    </header>

    <h4>Como Funciona o Módulo</h4>
    <p>O fluxo de prospecção do Outreach foi desenhado em três pilares principais:</p>
    <ul>
        <li><strong>Ficha do Contato (CRM):</strong> Centraliza dados de contato, empresa, status atual da negociação e uma linha do tempo para registrar anotações de reuniões, telefonemas ou pedidos.</li>
        <li><strong>Modelos Dinâmicos:</strong> Permite escrever mensagens padrão ricas em texto utilizando tags de substituição que são preenchidas com os dados de cada lead no momento do envio.</li>
        <li><strong>Disparo Escalonado em Fila:</strong> Envia e-mails em lote sem travar o painel e sem arriscar a reputação do seu domínio.</li>
    </ul>

    <h4>Variáveis Disponíveis nos Modelos</h4>
    <p>Você pode utilizar as seguintes tags no <strong>Assunto</strong> e no <strong>Corpo</strong> dos seus modelos de e-mail:</p>

    <table class="help-variables-table">
        <thead>
            <tr>
                <th>Tag</th>
                <th>Origem no Cadastro</th>
                <th>Exemplo de Saída</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>@{{primeiro_nome}}</code></td>
                <td>Primeira palavra do campo Nome</td>
                <td>Carlos</td>
            </tr>
            <tr>
                <td><code>@{{nome}}</code></td>
                <td>Nome completo cadastrado</td>
                <td>Carlos Eduardo Silva</td>
            </tr>
            <tr>
                <td><code>@{{empresa}}</code></td>
                <td>Nome da Empresa (ou fallback padrão)</td>
                <td>Acme Comércio</td>
            </tr>
            <tr>
                <td><code>@{{cargo}}</code></td>
                <td>Cargo do lead na empresa</td>
                <td>Diretor Comercial</td>
            </tr>
            <tr>
                <td><code>@{{email}}</code></td>
                <td>Endereço de e-mail do destinatário</td>
                <td>carlos@acme.com</td>
            </tr>
            <tr>
                <td><code>@{{telefone}}</code></td>
                <td>Telefone ou WhatsApp do lead</td>
                <td>(11) 98765-4321</td>
            </tr>
            <tr>
                <td><code>@{{site}}</code></td>
                <td>URL do site do contato</td>
                <td>https://acme.com</td>
            </tr>
        </tbody>
    </table>

    <h4>Proteção Contra Blacklist e Filtros de Spam</h4>
    <div class="help-callout-info">
        <strong>Importante:</strong> Disparar dezenas de mensagens simultâneas em um único segundo aciona os filtros anti-spam dos provedores de destino (Gmail, Outlook, etc.).
    </div>
    <p>
        Para garantir alta entregabilidade, o Outreach programa cada e-mail com um <strong>intervalo aleatório de 45 a 90 segundos</strong> entre um envio e outro. Para os servidores do Brevo e de quem recebe, a ação se comporta exatamente como se você estivesse redigindo e enviando cada e-mail manualmente.
    </p>

    <h4>Processamento da Fila no Servidor (Queue)</h4>
    <p>
        Como os envios são enfileirados de forma assíncrona com atraso programado, certifique-se de que o worker de filas do Laravel esteja ativo no servidor de produção:
    </p>
    <div class="code">
        php artisan queue:work --tries=2
    </div>

    <h4>Boas Práticas de Prospecção</h4>
    <ul>
        <li><strong>Foco em Texto Puro:</strong> E-mails comerciais pessoais convertem mais quando não parecem newsletters promocionais. Evite fundos coloridos e imagens pesadas.</li>
        <li><strong>Chamada Única para Ação (CTA):</strong> Faça uma pergunta simples ao final (ex: <em>"Faz sentido conversarmos 10 minutos esta semana?"</em>).</li>
        <li><strong>Acompanhe o Histórico:</strong> Ao receber a resposta de um lead, acesse a ficha dele no menu Contatos, mude o status para <code>Em conversa</code> e registre uma anotação na Linha do Tempo.</li>
    </ul>

    <x-configurable-plugin-values plugin="Outreach" />
</div>
