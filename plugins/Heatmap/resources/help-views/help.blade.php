<div class="plugin-help-content">

    {{-- Banner de Introdução --}}
    <header>
        <h3>
            <x-lucide-flame class="lucid-icon" /> Mapas de calor semânticos
        </h3>
        <p>
            Descubra exatamente onde os visitantes clicam e visualizem manchas térmicas sobrepostas diretamente sobre os elementos, botões e links reais do seu site.
        </p>
    </header>

    {{-- Passo 1: Como Funciona o Rastreamento --}}
    <h3>
        1. Funcionamento automático e silencioso
    </h3>
    <p>
        O plugin é injetado automaticamente nas páginas públicas do site através do gerenciador de assets do sistema (<code>AssetManager</code>), operando de forma assíncrona com <code>defer</code> no rodapé para não impactar a velocidade de carregamento do tema.
    </p>

    <blockquote>
        <strong>Privacidade e performance (Zero lentidão):</strong>
        <p>
            Os cliques não são enviados um a um. O script acumula os seletores em memória e envia lotes otimizados a cada 15 segundos ou quando o visitante fecha a aba usando a API nativa <code>navigator.sendBeacon</code>.
        </p>
    </blockquote>

    {{-- Passo 2: Rastreamento Semântico por Elemento --}}
    <h3>
        2. Rastreamento inteligente por elemento (DOM)
    </h3>
    <p>
        Diferente de mapas de calor antigos que gravavam coordenadas de tela estáticas e quebravam em celulares, este plugin vincula o clique diretamente ao <strong>seletor do elemento HTML</strong>.
    </p>

    <ul>
        <li>
            <strong>Precisão geométrica absoluta:</strong> Ao abrir o visualizador, o sistema localiza onde cada elemento está renderizado no momento e projeta o calor exatamente no seu centro físico.
        </li>
        <li>
            <strong>Fidelidade total:</strong> Se a página mudar de tamanho ou o texto quebrar mais linhas em telas menores, as manchas térmicas acompanham os elementos automaticamente.
        </li>
    </ul>

    {{-- Passo 3: Configuração da Whitelist --}}
    <h3>
        3. Seletores monitorados (Whitelist)
    </h3>
    <p>
        Para evitar poluição com cliques acidentais em áreas vazias ou no fundo da página, o plugin monitora apenas elementos interativos configurados em <strong>Configurações &gt; Geral</strong>:
    </p>
    <div class="code">
        a, button, input, select, textarea, label, summary, [role="button"], [role="tab"], [data-track], [data-action], img
    </div>
    <p>
        Você pode personalizar essa lista a qualquer momento para incluir classes ou seletores específicos do seu tema (por exemplo: <code>.card-link</code>, <code>[data-meu-banner]</code>).
    </p>

    {{-- Passo 4: Como Interpretar as Cores --}}
    <h3>
        4. Escala de intensidade térmica
    </h3>
    <p>O algoritmo de renderização no Canvas calcula a proporção relativa de cliques entre todos os elementos da página:</p>

    <table>
        <thead>
            <tr>
                <th>Cor</th>
                <th>Intensidade</th>
                <th>Significado</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong style="color: #ef4444;">Vermelho / Laranja</strong></td>
                <td>Muito alta</td>
                <td>Ponto de maior conversão da página com altíssimo volume de cliques acumulados.</td>
            </tr>
            <tr>
                <td><strong style="color: #eab308;">Amarelo</strong></td>
                <td>Média / Alta</td>
                <td>Elemento de interesse frequente com bom engajamento dos visitantes.</td>
            </tr>
            <tr>
                <td><strong style="color: #22c55e;">Verde</strong></td>
                <td>Média</td>
                <td>Interações moderadas distribuídas pela estrutura da página.</td>
            </tr>
            <tr>
                <td><strong style="color: #06b6d4;">Ciano / Azul</strong></td>
                <td>Baixa</td>
                <td>Cliques esparsos ou interações ocasionais.</td>
            </tr>
        </tbody>
    </table>

    {{-- Passo 5: Boas Práticas e Isolamento --}}
    <h3>
        5. Isolamento de administradores
    </h3>
    <p>
        Para manter as métricas 100% confiáveis, o rastreador ignora automaticamente qualquer navegação efetuada por usuários logados na administração, garantindo que testes internos não interfiram nos dados de comportamento do público real.
    </p>

    <blockquote>
        <strong>Dica de visualização:</strong>
        <p>
            No visualizador, utilize os botões <strong style="display: inline">Desktop</strong> e <strong style="display: inline">Mobile</strong> para alternar a moldura da página instantaneamente. Os elementos que existem apenas em um dos layouts (como o menu hambúrguer) acenderão suas manchas térmicas automaticamente.
        </p>
    </blockquote>

    <x-configurable-plugin-values plugin="Heatmap" />
</div>
