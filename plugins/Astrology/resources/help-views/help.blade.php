<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-compass class="lucid-icon" /> Mapas Astrais (Astrology)
        </h3>
        <p>
            Cálculo astronômico profissional de efemérides (Swiss Ephemeris), gerenciamento de mapas de nascimento e renderização visual de mandalas em SVG modular por temas.
        </p>
    </header>

    <h4>Como Exibir Mapas no Site</h4>
    <p>Para embutir a mandala de um mapa público em qualquer página ou post do blog, use o shortcode:</p>
    <div class="code">
        [natal_chart slug="nome-do-mapa"]<br>
        [natal_chart slug="nome-do-mapa" theme="classic"]
    </div>

    <h4>Helper PHP para Desenvolvedores</h4>
    <p>Você pode renderizar a mandala diretamente em templates Blade do seu tema:</p>
    <div class="code">
        &#123;&#123; renderNatalChart('nome-do-mapa') &#125;&#125;<br>
        &#123;&#123; renderNatalChart('nome-do-mapa', 'classic') &#125;&#125;
    </div>

    <h4>Como Criar Novos Temas</h4>
    <p>
        Para adicionar um novo design de mapa, basta criar uma nova pasta dentro de:
        <code>public/plugins/astrology/themes/nome-do-tema/</code> contendo um manifesto <code>theme.json</code> e os arquivos SVG correspondentes.
    </p>

    <x-configurable-plugin-values plugin="Astrology" />
</div>
