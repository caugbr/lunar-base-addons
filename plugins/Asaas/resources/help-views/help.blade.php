<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-credit-card class="lucid-icon" /> Asaas Gateway de Pagamento
        </h3>
        <p>
            Módulo desacoplado de cobrança e checkout seguro integrado à API do Asaas com arquitetura <strong>Banco Cego (Zero PII - LGPD)</strong>.
        </p>
    </header>

    <h4>1. Configuração do Ambiente e Chaves de API</h4>
    <p>
        Acesse <strong>Admin → Configurações → Geral</strong> (seção <em>Asaas Gateway</em>) para alternar entre o ambiente de <strong>Sandbox (Testes)</strong> e <strong>Produção</strong>, informando as respectivas chaves de API obtidas no seu painel do Asaas.
    </p>

    <h4>2. Configuração do Webhook no Painel do Asaas</h4>
    <p>
        Para que o sistema receba confirmações automáticas de pagamentos (PIX, Cartão e Boleto), configure a URL de Webhook no painel do Asaas:
    </p>

    <div class="code" style="font-family: monospace; padding: 10px; background: #1e293b; color: #38bdf8; border-radius: 6px; margin: 10px 0;">
        {{ url('/api/v1/asaas/webhook') }}
    </div>

    <ul>
        <li><strong>Versão da API do Webhook:</strong> v3</li>
        <li><strong>Eventos recomendados:</strong> Pagamento recebido, Pagamento confirmado, Pagamento estornado/com falha.</li>
        <li><strong>Token de Autenticação:</strong> Copie o token gerado no Asaas e cole no campo <em>Token do Webhook</em> nas Configurações do Lunar Base.</li>
    </ul>

    <h4>3. Segurança e Privacidade (Estratégia Banco Cego - LGPD)</h4>
    <p>
        Este plugin foi projetado com conformidade estrita com a LGPD:
    </p>
    <ul>
        <li><strong>Nenhum dado pessoal do comprador (Nome, CPF, Endereço) é armazenado no banco local.</strong></li>
        <li>A tabela local salva apenas o ID da cobrança (`payment_id`), ID do cliente no Asaas (`customer_id`), valor e status.</li>
        <li>A coleta de dados é feita de forma segura na página de checkout hospedada pelo próprio Asaas (Payment Links), eliminando riscos e responsabilidade legal sobre vazamentos de dados de cartão ou documentos.</li>
    </ul>

    <h4>4. Integração no Código (Para Desenvolvedores)</h4>
    <p>
        Qualquer plugin ou recurso do seu site pode solicitar a criação de cobranças e escutar os pagamentos aprovados via Hook do sistema:
    </p>

    <h5>A. Solicitando um Checkout de Pagamento:</h5>
    <div class="code" style="font-family: monospace; padding: 10px; background: #1e293b; color: #f1f5f9; border-radius: 6px; margin: 10px 0;">
        $asaas = app(\Plugins\Asaas\Services\AsaasPaymentService::class);<br>
        $result = $asaas->generateCheckoutLink(50.00, 'Nome do Produto', 'pedido_123');<br>
        // $result['invoice_url'] contem o link de pagamento do Asaas
    </div>

    <h5>B. Escutando Pagamentos Aprovados (Hook):</h5>
    <div class="code" style="font-family: monospace; padding: 10px; background: #1e293b; color: #f1f5f9; border-radius: 6px; margin: 10px 0;">
        HookManager::listen('asaas.payment_approved', function ($data) {<br>
        &nbsp;&nbsp;&nbsp;&nbsp;$reference = $data['external_reference']; // ex: 'pedido_123'<br>
        &nbsp;&nbsp;&nbsp;&nbsp;// Lógica para entregar o produto ao comprador...<br>
        });
    </div>

    <blockquote>
        <strong>Dica de Testes em Sandbox:</strong>
        <p>
            Ao testar no ambiente de Sandbox do Asaas, você pode simular o pagamento de cobranças utilizando a ferramenta de teste de PIX e Cartão oferecida pelo próprio painel do Asaas.
        </p>
    </blockquote>
</div>
