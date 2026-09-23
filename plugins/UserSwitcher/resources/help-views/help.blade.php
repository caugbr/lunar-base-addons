<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-user-check class="lucid-icon" /> Alternância de Usuário (User Switcher)
        </h3>
        <p>
            Permite que administradores assumam a identidade de qualquer usuário do sistema para fins de suporte, testes de permissão e resolução de problemas.
        </p>
    </header>

    <h4>Como Utilizar</h4>
    <p>
        1. Vá até <strong>Admin &rarr; Usuários</strong>.<br>
        2. Na coluna de ações do usuário desejado, clique no botão <strong>Assumir</strong>.<br>
        3. O sistema autenticará você instantaneamente como aquele usuário.<br>
        4. Um banner laranja permanente será exibido no topo do painel indicando quem você está simulando.<br>
        5. Para retornar ao seu usuário original, basta clicar no botão <strong>Voltar à minha conta</strong>.
    </p>

    <blockquote>
        <strong>Segurança:</strong> Todas as trocas de identidade e retornos são registradas na trilha de auditoria do sistema (<code>log_admin</code>).
    </blockquote>
</div>
