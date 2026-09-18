<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-archive-x class="lucid-icon" />
            Archive Vault
        </h3>
        <p>
            Retém cópias de segurança de publicações e páginas excluídas definitivamente, permitindo auditoria e restauração posterior.
        </p>
    </header>

    <h4>Como funciona</h4>
    <p>
        O plugin monitora a limpeza da lixeira no sistema. Ao esvaziar a lixeira ou remover um registro de forma definitiva, um snapshot completo (incluindo metadados e termos associados) é preservado no cofre antes da exclusão física da tabela original.
    </p>

    <h4>Onde acessar</h4>
    <p>
        O gerenciamento do cofre fica disponível no menu lateral em <strong>Ferramentas &gt; Conteúdo excluído</strong>, assim como através do cartão dedicado na própria página de ferramentas.
    </p>

    <h4>Recursos disponíveis no painel</h4>
    <ul>
        <li><strong>Filtros por tipo:</strong> Navegação rápida entre posts e páginas arquivadas.</li>
        <li><strong>Inspeção:</strong> Visualização do texto original e do payload bruto em JSON.</li>
        <li><strong>Restauração:</strong> Permite ressuscitar qualquer item diretamente para o status de Rascunho, tratando duplicidade de slug automaticamente.</li>
        <li><strong>Expurgo definitivo:</strong> Permite a administradores remover permanentemente registros do cofre.</li>
    </ul>

    <x-configurable-plugin-values plugin="ArchiveVault" />
</div>