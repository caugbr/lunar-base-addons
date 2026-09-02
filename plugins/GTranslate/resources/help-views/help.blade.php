<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-languages class="lucid-icon" />
            GTranslate (Google Tradutor)
        </h3>
        <p>
            Tradução automática e instantânea do site para múltiplos idiomas utilizando a engine do Google Translate.
        </p>
    </header>

    <h4>Como exibir o seletor no site</h4>
    <p>
        Para exibir o seletor de idiomas no menu, cabeçalho ou barra de acessibilidade do seu tema, insira a tag do componente em qualquer view Blade pública:
    </p>

    <div class="code" style="padding: 10px; background: #1e293b; color: #38bdf8; border-radius: 6px; font-family: monospace;">
        &lt;x-gtranslate::selector /&gt;
    </div>

    <h4>Como funciona</h4>
    <p>
        O plugin carrega a biblioteca oficial do Google no rodapé da página. Ao selecionar um idioma, o script varre o conteúdo da página e traduz textos, menus, botões e postagens em tempo real sem recarregar a página.
    </p>

    <!-- Exibe as variáveis configuráveis do plugin -->
    <x-configurable-plugin-values plugin="GTranslate" />
</div>
