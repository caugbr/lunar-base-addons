<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-repeat class="lucid-icon" />
            Redirects & 404 Monitor
        </h3>
        <p>
            Gerencia regras de redirecionamento HTTP (301 permanente e 302 temporário) e monitora automaticamente tentativas de acesso a páginas inexistentes (erros 404) no site público.
        </p>
    </header>

    <h4>Integração com o Painel Administrativo</h4>
    <p>O plugin se integra nativamente em duas seções principais da barra lateral e painéis do sistema:</p>
    <ul>
        <li><strong>Ferramentas → Redirecionamentos:</strong> Acesso à listagem e criação de regras de redirecionamento, contando com um box dedicado na página principal de ferramentas.</li>
        <li><strong>Referências → Erros 404:</strong> Painel de monitoramento contendo o histórico de URLs quebradas acessadas por visitantes, exibindo contagem de acessos (hits), origem (referrer) e um botão de conversão rápida.</li>
    </ul>

    <h4>Como Funciona a Captura e Resolução</h4>
    <p>O plugin opera de forma autônoma através de dois middlewares integrados ao grupo <code>web</code>:</p>
    <ul>
        <li><strong>HandleRedirects:</strong> Verifica em tempo de execução se a URL requisitada possui uma regra ativa. Caso afirmativo, executa o redirecionamento HTTP configurado.</li>
        <li><strong>CaptureNotFound:</strong> Monitora respostas com status HTTP 404 no front-end (ignorando rotas de admin, API e arquivos estáticos/ocultos), armazenando e agrupando os acessos para análise.</li>
    </ul>

    <h4>Conversão em 1 Clique</h4>
    <p>
        Na tela de <strong>Erros 404</strong>, ao clicar no botão de redirecionar de um link quebrado, um modal é aberto para que você defina a nova URL de destino. Ao salvar, a regra 301 é criada automaticamente e o log de erro 404 correspondente é limpo da tabela.
    </p>

    <x-configurable-plugin-values plugin="Redirects" />
</div>
