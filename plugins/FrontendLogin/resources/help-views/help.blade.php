<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-log-in class="lucid-icon" /> Frontend Login
        </h3>
        <p>
            Permite embutir formulários de autenticação diretamente nas páginas públicas ou no cabeçalho do site, sem a necessidade de redirecionar o visitante para a rota padrão de login.
        </p>
    </header>

    <h4>Como Incorporar via Shortcode</h4>
    <p>
        Você pode colar o shortcode em qualquer área de texto do editor (Páginas ou Posts do Blog):
    </p>

    <div class="code">
        [login_form]
    </div>

    <p>Para forçar o redirecionamento para o painel administrativo após o acesso:</p>

    <div class="code">
        [login_form redirect="admin"]
    </div>

    <h4>Como Incorporar via Código no Tema</h4>
    <p>
        Em arquivos Blade do seu tema (como no cabeçalho ou barra lateral), utilize a função global auxiliar:
    </p>

    <div class="code">
        {!! frontend_login_form() !!}
    </div>

    <p>Ou especificando o destino:</p>

    <div class="code">
        {!! frontend_login_form('admin') !!}
    </div>

    <h4>Parâmetros Disponíveis</h4>
    <p>
        O parâmetro <code>redirect</code> aceita dois valores:
    </p>
    <ul>
        <li>
            <strong>current</strong> (padrão): o usuário faz login e permanece na página atual. Ao clicar em sair, também retorna à página de onde partiu.
        </li>
        <li>
            <strong>admin</strong>: após a autenticação bem-sucedida, o usuário é encaminhado diretamente para o painel de administração.
        </li>
    </ul>

    <blockquote>
        <strong>Comportamento para Usuários Autenticados:</strong>
        <p>
            Quando o visitante já está logado, o formulário oculta os campos de entrada automaticamente e renderiza o nome do usuário com link direto para o perfil e botão de encerramento de sessão (logout).
        </p>
    </blockquote>

    <h4>Segurança Integrada</h4>
    <p>
        O formulário respeita nativamente as configurações globais de segurança do Lunar Base:
    </p>
    <ul>
        <li>Proteção por captcha (Cloudflare Turnstile), caso habilitado em Configurações.</li>
        <li>Desafio de autenticação em dois fatores (2FA) para contas com a verificação ativa.</li>
        <li>Proteção contra ataques de CSRF e regeneração segura de sessão.</li>
    </ul>

    <x-configurable-plugin-values plugin="FrontendLogin" />
</div>
