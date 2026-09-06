<div class="plugin-help-content">
    <header>
        <h3>
            <x-lucide-headphones class="lucid-icon" />
            Article Reader (Text-to-Speech)
        </h3>
        <p>
            Adiciona um leitor de áudio inteligente aos posts e páginas públicas do site. O plugin utiliza a <strong>Web Speech API</strong> nativa dos navegadores modernos para narrar o texto em voz em tempo real, oferecendo controles de reprodução, barra de progresso e seletor de velocidade sem gerar custos com APIs externas ou ocupar espaço em disco no servidor.
        </p>
    </header>

    <h4>Configurações no Painel</h4>
    <p>
        O plugin injeta opções de controle diretamente na aba <strong>Leitura</strong> das Configurações Gerais do sistema (<code>Configurações &rarr; Leitura</code>), permitindo ao administrador escolher exatamente onde o player deve ser exibido:
    </p>
    <ul>
        <li><strong>Posts:</strong> Habilita a narração automática no topo das publicações do blog.</li>
        <li><strong>Páginas:</strong> Habilita a narração no topo das páginas públicas do CMS.</li>
    </ul>

    <h4>Pontos de Injeção (Hooks)</h4>
    <p>O plugin se conecta de forma dinâmica e condicional aos ganchos do tema ativo:</p>
    <ul>
        <li><strong><code>post.before_content</code>:</strong> Injeta a barra do player de áudio logo acima do conteúdo principal nas publicações do blog (quando habilitado).</li>
        <li><strong><code>page.before_content</code>:</strong> Renderiza o player de áudio no topo do conteúdo nas páginas públicas do CMS (quando habilitado).</li>
    </ul>

    <h4>Estimativa de Tempo de Leitura / Áudio</h4>
    <p>
        O cálculo do tempo estimado exibido no player respeita dinamicamente a configuração global de velocidade de leitura do sistema definida em <code>setting('reading.words_count')</code> (padrão de 200 palavras por minuto).
    </p>

    <h4>Recursos e Controles do Player</h4>
    <ul>
        <li><strong>Play / Pause:</strong> Controle de reprodução com retomada do ponto exato onde a leitura parou.</li>
        <li><strong>Barra de Progresso em Tempo Real:</strong> Acompanha o avanço visual da leitura frase por frase através do evento <code>onboundary</code>.</li>
        <li><strong>Seletor de Velocidade:</strong> Permite ao visitante alternar entre <code>1x</code>, <code>1.25x</code>, <code>1.5x</code> e <code>2x</code> sem pausar a narração.</li>
        <li><strong>Texto Limpo Server-Side:</strong> O texto a ser narrado é higienizado pelo Laravel via PHP, removendo tags HTML, códigos e scripts para garantir uma pronúncia limpa.</li>
    </ul>

    <h4>Como Personalizar o Visual no Tema</h4>
    <p>
        O player foi construído no formato de pílula responsiva moderna. Caso o seu tema precise sobrescrever as cores ou o arredondamento das bordas, basta estilizar a classe container no CSS do tema:
    </p>

    <div class="code">
        .lunar-article-reader {
            background-color: var(--color-bg-card);
            border-color: var(--color-border);
        }
    </div>

    <h4>Compatibilidade & Privacidade</h4>
    <p>
        Por utilizar a API nativa do próprio dispositivo do visitante (Chrome, Safari, Edge, iOS e Android), nenhuma informação de áudio é trafegada para servidores externos, garantindo privacidade total aos leitores e velocidade instantânea de carregamento.
    </p>

    <x-configurable-plugin-values plugin="ArticleReader" />
</div>
