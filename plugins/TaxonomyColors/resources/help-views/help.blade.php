<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-palette class="lucid-icon" />
            Cores de Taxonomias (Taxonomy Colors)
        </h3>
        <p>
            Permite associar paletas de cores customizadas a categorias, tags e termos das taxonomias do site para uso dinâmico em badges, cards e temas.
        </p>
    </header>

    <h4>Como funciona</h4>
    <p>
        O plugin injeta automaticamente um seletor visual de cor nativo (<code>&lt;input type="color"&gt;</code>) nas telas de criação e edição de termos em <strong>Admin → Taxonomias → Termos</strong>.
    </p>

    <h4>Como usar no seu Tema ou Blade</h4>
    <p>
        Para resgatar a cor cadastrada de um termo específico em qualquer template do site, utilize o helper global <code>getTermColor()</code>:
    </p>

    <h4>Parâmetros da Função</h4>
    <ul>
        <li><code>$termId</code> (int, obrigatório): ID do termo desejado.</li>
        <li><code>$default</code> (string, opcional): Cor de fallback em HEX/RGB caso nenhuma cor tenha sido definida (padrão: <code>#000000</code>).</li>
    </ul>

    <!-- Exibe as variáveis configuráveis se houver no plugin.json -->
    <x-configurable-plugin-values plugin="TaxonomyColors" />

    <!-- Exibe requisitos do Composer/PHP -->
    <x-plugin-dependencies plugin="TaxonomyColors" />
</div>
