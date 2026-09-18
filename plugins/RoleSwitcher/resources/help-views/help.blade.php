<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-arrow-left-right class="lucid-icon" /> Alternância de Papéis (Role Switcher)
        </h3>
        <p>
            Permite que usuários com papéis elevados (como Administradores e Editores) assumam temporariamente a visualização e permissões de outros papéis (como Aluno, Autor ou Assinante) sem alterar os dados do banco.
        </p>
    </header>

    <h4>Como Funciona a Matriz de Permissões</h4>
    <p>
        Em <strong>Admin &rarr; Alternância de Papéis</strong>, defina quais papéis de origem têm autorização para se transformar em quais papéis de destino. Nenhum usuário poderá assumir um papel que não esteja expressamente ticado na matriz.
    </p>

    <blockquote>
        <strong>Ciclo da Simulação:</strong>
        <p>
            1. O usuário acessa a página <strong>Meu Perfil</strong>.<br>
            2. Seleciona no painel de simulação o papel desejado dentre os autorizados para ele.<br>
            3. O sistema passa a responder via gancho <code>has_role</code> e variáveis de sessão como se ele realmente pertencesse àquele perfil.<br>
            4. A qualquer momento, basta clicar em <strong>Voltar ao Papel Real</strong> para restaurar o acesso original.
        </p>
    </blockquote>

    <h4>Persistência Segura</h4>
    <p>
        A matriz de papéis utiliza as opções globais do sistema (<code>getOption</code> / <code>setOption</code>), mantendo os dados preservados e isolados no grupo <code>_system_options</code>.
    </p>

    <x-configurable-plugin-values plugin="RoleSwitcher" />
</div>
