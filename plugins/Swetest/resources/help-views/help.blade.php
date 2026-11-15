<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-cpu class="lucid-icon" /> Swetest (Swiss Ephemeris Local)
        </h3>
        <p>
            Motor astronômico local baseado no binário <code>swetest</code> para cálculo de posições planetárias, cúspides de casas e retorno solar sem depender de endpoint HTTP externo.
        </p>
    </header>

    <h4>Requisitos do servidor</h4>
    <p>
        Este plugin executa o binário nativo (<code>swetest64.exe</code> no Windows ou <code>swetest</code> no Linux) via <code>exec()</code>. Em servidores Linux, confirme a permissão de execução:
    </p>
    <div class="code">
        chmod +x plugins/Swetest/swetest/linux/swetest
    </div>

    <h4>Diagnóstico pelo terminal</h4>
    <p>Para validar se o servidor atual permite rodar cálculos localmente:</p>
    <div class="code">
        php artisan swetest:check
    </div>

    <h4>Integração com o plugin Astrology</h4>
    <p>
        Quando o campo <code>apiEndpoint</code> nas configurações do plugin <strong>Astrology</strong> estiver vazio, ele utiliza automaticamente este plugin localmente em memória (sem HTTP e sem uso de tokens). Caso o servidor não suporte <code>exec()</code>, mantenha o <code>apiEndpoint</code> apontando para o serviço remoto.
    </p>

    <h4>Endpoint HTTP opcional e tokens no .env</h4>
    <p>
        Caso deseje que outros sites consultem este servidor via <code>POST /api/swetest/calculate</code>, habilite <code>exposeApiEndpoint</code> e defina o array <code>tokens</code> em <code>config/pluginSettings.php</code>. Guarde os segredos reais no arquivo <code>.env</code> do projeto e referencie com <code>env()</code> para não expor tokens no repositório:
    </p>
    <div class="code">
        // No arquivo .env:<br>
        SWETEST_TOKEN_SITE1=seu-token-aleatorio-de-32-bytes<br>
        SWETEST_TOKEN_SITE2=outro-token-aleatorio-de-32-bytes<br>
        <br>
        // Em config/pluginSettings.php:<br>
        'Swetest' =&gt; [<br>
        &nbsp;&nbsp;&nbsp;&nbsp;'exposeApiEndpoint' =&gt; true,<br>
        &nbsp;&nbsp;&nbsp;&nbsp;'tokens' =&gt; [<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;['client' =&gt; 'meudominio.com', 'token' =&gt; env('SWETEST_TOKEN_SITE1'), 'active' =&gt; true],<br>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;['client' =&gt; 'meudominio2.com', 'token' =&gt; env('SWETEST_TOKEN_SITE2'), 'active' =&gt; true],<br>
        &nbsp;&nbsp;&nbsp;&nbsp;],<br>
        ],
    </div>
    <p>
        Sempre que alterar tokens no <code>.env</code> em produção, execute <code>php artisan config:cache</code> para atualizar a configuração em memória.
    </p>

    <x-configurable-plugin-values plugin="Swetest" />
</div>
