<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-globe class="lucid-icon" />
            Sitemap XML
        </h3>
        <p>
            Gera um mapa do site em formato XML otimizado para indexação em mecanismos de busca como Google, Bing e Yahoo.
        </p>
    </header>

    <h4>Funcionamento Dinâmico</h4>
    <p>O sitemap não gera arquivos físicos estáticos no servidor. Ele é processado e servido instantaneamente através da rota pública <code>/sitemap.xml</code> respeitando as regras de publicação e visibilidade configuradas no painel.</p>

    <h4>Configurações de Inclusão</h4>
    <p>Você pode controlar quais entidades aparecem no sitemap acessando <strong>Admin → Configurações</strong>, onde é possível alternar a inclusão de Posts, Páginas e Taxonomias de forma independente.</p>

    <x-configurable-plugin-values plugin="Sitemap" />
</div>
