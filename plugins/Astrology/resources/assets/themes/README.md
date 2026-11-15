
# Guia de especificação de temas para mapas astrais

Este guia estabelece os parâmetros, as regras de herança e as restrições técnicas para criação e manutenção de temas vetoriais no plugin de astrologia, abrangendo mapas simples (`theme.json`) e mapas em sobreposição (`theme-biwheel.json`).

---

## Estrutura de diretórios

Cada tema deve ser encapsulado dentro de seu próprio diretório em `public/plugins/astrology/themes/{slug-do-tema}/` ou `Astrology/resources/assets/themes/{slug-do-tema}/`.

```text
meu-tema/
├── theme.json
├── theme-biwheel.json (opcional — habilita o tema para sobreposição de dois mapas)
├── base.svg
├── base-biwheel.svg (obrigatório quando houver theme-biwheel.json)
├── background.svg (opcional)
├── logo.jpg (opcional)
├── signs/
│   ├── aries.svg
│   ├── taurus.svg
│   ├── ...
│   └── pisces.svg
└── planets/
    ├── sun.svg
    ├── moon.svg
    ├── ...
    └── south_node.svg
```

Observações sobre carregamento e cache:
- Caso exista uma pasta com o mesmo `slug` em `public/plugins/astrology/themes/` e em `Astrology/resources/assets/themes/`, o diretório em `public/` tem prioridade.
- Em ambiente com `app.debug = true`, o `ThemeManager` relê os manifestos do disco a cada requisição. Em produção, os manifestos ficam em cache até a execução de `ThemeManager::clearCache()` ou `php artisan cache:clear`.
- Apenas temas que possuírem `theme-biwheel.json` aparecem na seleção de temas do modo bi-wheel (`ThemeManager::getBiwheelThemes()`).

---

## Ordem de empilhamento vetorial

Tanto o construtor de mapa simples (`ChartSvgBuilder`) quanto o de roda dupla (`BiwheelChartSvgBuilder`) compilam o SVG da prancha A4 seguindo esta ordem de profundidade:

```text
1. Fundo do canvas (rect sólido com canvas.backgroundColor)
2. Imagem decorativa de fundo (assets.background — opcional)
3. Cabeçalho da prancha (informações dos mapas e assets.logo)
4. Grupo #mandala-wheel (espaço interno 1000x1000 escalado por layout.wheel):
   4.1. Faixas dinâmicas de cores (bands)
   4.2. Mandala estrutural (assets.base — base.svg ou base-biwheel.svg)
   4.3. Glifos dos doze signos (signsDir)
   4.4. Linhas e números das 12 casas (mapa simples ou mapa interno do bi-wheel)
   4.5. Linhas centrais de aspectos (internos ou aspectos cruzados)
   4.6. Órbita(s) planetária(s):
        - Mapa simples: régua de ticks, linhas-guia, glifos, graus e minutos
        - Bi-wheel: anel interno (rings.inner), anel externo (rings.outer), marcadores de ângulos externos e ticks opcionais de casas externas
5. Blocos analíticos inferiores:
   - Mapa simples: grade triangular de aspectos + matriz 4x3 de elementos e qualidades
   - Bi-wheel: matriz retangular N x M de aspectos cruzados + painel lateral de posições externas e sobreposição de casas
6. Rodapé da prancha (modules.footerText)
```

---

## Dicionário do manifesto `theme.json`

### 1. Metadados gerais
* `name` *(string, obrigatório)*: nome legível do tema exibido nos seletores da interface.
* `slug` *(string, obrigatório)*: identificador único correspondente ao nome da pasta do tema.
* `version` *(string)*: versão do manifesto.
* `author` *(string)*: autor ou responsável pelo design.
* `description` *(string)*: resumo técnico do estilo visual.

### 2. Canvas e módulos (`canvas` e `modules`)
* `canvas.viewBoxWidth` *(inteiro, padrão: 1000)*: largura total da prancha SVG.
* `canvas.viewBoxHeight` *(inteiro, padrão: 1414)*: altura total da prancha SVG em formato vertical A4.
* `canvas.backgroundColor` *(string, padrão: "#ffffff")*: cor sólida de fundo da prancha. Utilize `"transparent"` para omitir o retângulo de fundo.
* `modules.chartInfo` *(boolean, padrão: false)*: exibe os dados de nascimento e localização no cabeçalho superior.
* `modules.logo` *(boolean, padrão: false)*: renderiza a imagem definida em `assets.logo` no canto superior direito.
* `modules.footerText` *(boolean, padrão: false)*: exibe o texto configurado em `layout.footer.text` na base da prancha.

### 3. Layout da prancha A4 (`layout`)
O espaço interno da mandala opera sempre em `1000 x 1000 px` (centro em `500, 500`) e é posicionado na prancha através de `layout.wheel`:

* `layout.wheel.centerX` *(float, padrão: 500)*: coordenada X do centro da mandala na prancha A4.
* `layout.wheel.centerY` *(float, padrão: 530)*: coordenada Y do centro da mandala na prancha A4.
* `layout.wheel.scale` *(float, padrão: 0.85)*: fator de escala aplicado ao grupo `#mandala-wheel`.
* `layout.aspectGrid` *(objeto)*: posição `x`, `y` e `cellSize` da grade triangular de aspectos.
* `layout.elementsQualities` *(objeto)*: posição `x` e `y` da matriz de elementos e qualidades.
* `layout.footer.text` *(string)*: texto exibido quando `modules.footerText` está ativo.

### 4. Ativos estáticos (`assets`)
* `background` *(string|null)*: caminho relativo para imagem decorativa de fundo (SVG, PNG ou WebP).
* `base` *(string, obrigatório)*: arquivo SVG contendo os traçados fixos da mandala (`base.svg`) com fundo transparente.
* `logo` *(string|null)*: logotipo exibido no cabeçalho superior direito (`x=770, y=35`).
* `signsDir` *(string, padrão: "signs/")*: diretório com os glifos dos 12 signos em minúsculo (`aries.svg`, `taurus.svg`, etc.).
* `planetsDir` *(string, padrão: "planets/")*: diretório com os glifos dos corpos celestes (`sun.svg`, `moon.svg`, `north_node.svg`, etc.).
* `extension` *(string, padrão: "svg")*: extensão dos arquivos vetoriais de glifos.

### 5. Faixas dinâmicas de cor (`bands`)
Coleção de anéis concêntricos desenhados no espaço `1000 x 1000` da mandala, girando conforme o signo do Ascendente.

Cada objeto dentro de `bands` aceita:
* `name` *(string, obrigatório)*: identificador da faixa para rastreabilidade em mensagens de erro.
* `innerRadius` *(float, obrigatório)*: raio interno em pixels relativos ao espaço `1000 x 1000`.
* `outerRadius` *(float, obrigatório)*: raio externo (deve ser maior que `innerRadius`).
* `opacity` *(float, padrão: 1.0)*: opacidade entre `0.0` e `1.0`.
* `eclipticOffset` *(float, padrão: 0.0)*: deslocamento inicial em graus na eclíptica (`0` = 0° de Áries).
* `colors` *(array de strings, obrigatório)*: lista de cores CSS. A quantidade de itens deve ser rigorosamente **1, 2, 3, 4, 6, 12 ou 36**.

| Quantidade de cores | Arco por setor | Finalidade astrológica |
| :--- | :--- | :--- |
| 1 | 360° | Anel sólido contínuo |
| 2 | 180° | Hemisférios |
| 3 | 120° | Triplicidades / elementos condensados |
| 4 | 90° | Quadrantes zodiacais |
| 6 | 60° | Polaridades / pares de signos |
| 12 | 30° | Signos individuais |
| 36 | 10° | Decanatos |

### 6. Raios geométricos (`radii`)
Todos os raios são medidos a partir de `(500, 500)` dentro do grupo `#mandala-wheel`:

* `signsDistance` *(float)*: raio onde são ancorados os glifos dos 12 signos.
* `ticksDistance` *(float)*: raio externo dos tracinhos exatos de posição planetária.
* `ticksLength` *(float)*: comprimento em pixels dos tracinhos exatos.
* `planetsDistance` *(float)*: raio onde orbitam os glifos planetários.
* `housesInnerRadius` *(float)*: raio inicial das linhas das cúspides das casas.
* `housesOuterRadius` *(float)*: raio final das linhas das cúspides das casas.
* `aspectsInnerRadius` *(float)*: raio limite onde se conectam as linhas centrais de aspectos.

### 7. Casas astrológicas (`houses`)
* `showLines` *(boolean)*: desenha ou oculta as retas das 12 casas.
* `strokeWidth` *(float)*: espessura das linhas das casas intermediárias.
* `axisStrokeWidth` *(float)*: espessura das retas dos eixos principais (Casas 1, 4, 7 e 10).
* `color` *(string)*: cor das linhas das casas comuns e das bordas das grades SVG.
* `axisColor` *(string)*: cor de destaque dos eixos angulares.
* `showNumbers` *(boolean)*: exibe os números de 1 a 12 no interior do setor de cada casa.
* `numberColor` *(string)*: cor dos números das casas.

### 8. Dimensões, tipografia e régua de pontos (`dimensions` e `ticks`)
* `dimensions.signWidth` e `signHeight` *(float)*: dimensões do glifo de signo.
* `dimensions.planetWidth` e `planetHeight` *(float)*: dimensões do glifo de planeta.
* `dimensions.planetDegreeFontSize` *(float)*: tamanho da fonte do grau do planeta (os minutos usam `planetDegreeFontSize - 2`).
* `dimensions.planetDegreeOffset` *(float)*: deslocamento radial entre o centro do planeta e o texto do grau.
* `dimensions.degreeMinuteSpace` *(float)*: espaçamento radial entre o texto do grau e o texto do minuto.
* `dimensions.retrogradeFontSize` *(float)*: tamanho da fonte do indicador `"R"`.
* `dimensions.retrogradeRightGap` e `retrogradeTopGap` *(float)*: deslocamento X e Y do indicador `"R"` relativo ao centro do planeta.
* `ticks.color`, `ticks.strokeWidth` *(opcionais)*: cor e espessura dos traços exatos da posição planetária.
* `ticks.guideColor`, `ticks.guideWidth`, `ticks.guideDash` *(opcionais)*: estilo da linha tracejada que liga o traço exato ao glifo quando há desvio anti-colisão.

### 9. Aspectos (`aspects`)
* `showLines` *(boolean)*: desenha ou oculta as linhas de aspectos no miolo da mandala.
* `showAngleLines` *(boolean)*: inclui ou oculta linhas de aspectos envolvendo `Ascendant` e `Midheaven` dentro do círculo central.
* `showNodeLines` *(boolean)*: inclui ou oculta linhas de aspectos envolvendo nodos lunares dentro do círculo central.
* `strokeWidth` *(float)*: espessura das retas de aspectos.
* `colors` *(objeto)*: cores hexadecimais por tipo: `conjunction`, `opposition`, `trine`, `square`, `sextile`, `quincunx`, `semisquare`, `sesquiquadrate`, `semisextile`.

---

## Especificação do manifesto `theme-biwheel.json` (roda dupla)

O manifesto `theme-biwheel.json` configura a renderização de dois conjuntos de posições na mesma mandala (`BiwheelChartSvgBuilder` e `CrossAspectGridSvgBuilder`), mantendo o mapa simples (`theme.json`) completamente intacto.

### Regras de herança com o `theme.json`

Ao carregar um tema via `ThemeManager::getBiwheelTheme($slug)`:
1. O arquivo `theme.json` do mesmo diretório atua como base. Não é necessário repetir no `theme-biwheel.json` configurações compartilhadas como `assets.planetsDir`, `assets.signsDir`, `assets.extension`, `assets.logo` ou `aspects.colors`, a menos que queira sobrescrevê-las.
2. Mapas associativos (`canvas`, `modules`, `layout`, `assets`, `radii`, `houses`, `dimensions`, `aspects`, `rings`) sofrem mesclagem por chave sobre o tema base.
3. A chave `bands` é **substituída integralmente** quando declarada no `theme-biwheel.json` (nunca mesclada índice por índice com as faixas do mapa simples).

### Blocos específicos do `theme-biwheel.json`

#### 1. Cabeçalho e grade retangular (`layout`)
* `layout.header.innerX` *(float, padrão: 50)*: coordenada X do bloco de título/subtítulo do mapa interno.
* `layout.header.outerX` *(float, padrão: 430)*: coordenada X do bloco de título/subtítulo do mapa externo.
* `layout.header.y` *(float, padrão: 40)*: coordenada Y base dos dois blocos de cabeçalho.
* `layout.crossAspectGrid.x` *(float, padrão: 50)*: origem X da matriz retangular N x M.
* `layout.crossAspectGrid.y` *(float, padrão: 885)*: origem Y da matriz retangular N x M.
* `layout.crossAspectGrid.cellSize` *(float, padrão: 28)*: tamanho alvo das células da matriz. O `CrossAspectGridSvgBuilder` reduz esse valor automaticamente quando o número de pontos exigir ajuste para manter a matriz e o painel lateral de posições externas dentro da largura útil de `900 px` da prancha A4.
* `layout.crossAspectGrid.summaryWidth` *(float, padrão: 165)*: largura da coluna esquerda com as posições do mapa interno.

#### 2. Configuração independente por anel (`rings.inner` e `rings.outer`)
Substitui os raios únicos de planetas do mapa simples por dois blocos independentes, permitindo calibração distinta para a órbita interna (menor circunferência) e a coroa externa (maior circunferência):

* `planetsDistance` *(float)*: raio da órbita onde ficam os glifos do anel.
* `ticksInnerRadius` *(float)*: raio interno do tracinho exato de posição.
* `ticksOuterRadius` *(float)*: raio externo do tracinho exato de posição.
* `tickColor`, `tickWidth` *(opcionais)*: cor e espessura do tracinho exato do anel (padrão `#0284c7` no interno e `#db2777` no externo).
* `guideAnchorOffset` *(float com sinal)*: deslocamento radial a partir de `planetsDistance` onde a linha-guia anti-colisão ancora no planeta (positivo quando os ticks estão por fora da órbita do planeta; negativo quando estão por dentro).
* `planetWidth`, `planetHeight` *(float)*: dimensões dos glifos de planeta nesse anel.
* `degreeFontSize`, `degreeOffset`, `degreeMinuteSpace` *(float)*: tipografia e espaçamento radial dos textos de grau e minuto.
* `retrogradeFontSize`, `retrogradeRightGap`, `retrogradeTopGap` *(float)*: posição e tamanho do `"R"` de retrogradação.
* `labelDirection` *(string: `"inward"` | `"outward"`, padrão: `"inward"`)*: define se os textos de grau e minuto são posicionados em direção ao centro (`"inward"`) ou em direção à borda externa (`"outward"`) a partir do glifo do planeta.
* `minSeparationDeg` *(float)*: separação angular mínima (em graus) aplicada pelo algoritmo anti-colisão desse anel. Anéis internos comprimidos exigem valores maiores (ex.: `8.5°`–`9.0°`) do que coroas externas amplas (ex.: `5.0°`–`6.0°`).
* `showAngleMarkers` *(boolean, exclusivo de `rings.outer`)*: plota marcadores curtos `AC` e `MC` do mapa externo na borda da coroa externa.
* `showHouseTicks` *(boolean, exclusivo de `rings.outer`)*: plota traços curtos e números discretos das cúspides do mapa externo na borda da coroa externa (recomenda-se `false` por padrão para evitar ruído visual).

---

## Exemplo completo de `theme-biwheel.json`

Calibrado para o `base-biwheel.svg` com círculo externo de raio `470 px` (`940 px` de diâmetro), faixa de signos entre `296.25` e `375`, mapa interno entre `145` e `276.25` e coroa externa entre `400` e `470`:

```json
{
  "name": "Clássico Bi-Wheel A4",
  "slug": "classic",
  "version": "1.0",
  "description": "Tema bi-wheel A4 para sobreposição de dois mapas em anel interno e coroa externa.",

  "canvas": {
    "viewBoxWidth": 1000,
    "viewBoxHeight": 1414,
    "backgroundColor": "#ffffff"
  },

  "modules": {
    "chartInfo": true,
    "logo": true,
    "footerText": false
  },

  "layout": {
    "wheel": {
      "centerX": 500,
      "centerY": 475,
      "scale": 0.80
    },
    "header": {
      "innerX": 50,
      "outerX": 430,
      "y": 40
    },
    "crossAspectGrid": {
      "x": 50,
      "y": 885,
      "cellSize": 28,
      "summaryWidth": 165
    },
    "footer": {
      "text": "Mapa calculado via Lunar Base Astrology — Todos os direitos reservados."
    }
  },

  "assets": {
    "base": "base-biwheel.svg",
    "background": null
  },

  "bands": [
    {
      "name": "sign_segments",
      "innerRadius": 296.25,
      "outerRadius": 375,
      "opacity": 0.25,
      "eclipticOffset": 0,
      "colors": [
        "#f87171", "#fb923c", "#facc15", "#4ade80",
        "#2dd4bf", "#38bdf8", "#818cf8", "#c084fc",
        "#f472b6", "#94a3b8", "#a3e635", "#fb7185"
      ]
    },
    {
      "name": "inner_planets_bg",
      "innerRadius": 145,
      "outerRadius": 276.25,
      "opacity": 0.14,
      "eclipticOffset": 0,
      "colors": [
        "#cbd5e1"
      ]
    },
    {
      "name": "outer_planets_bg",
      "innerRadius": 400,
      "outerRadius": 470,
      "opacity": 0.10,
      "eclipticOffset": 0,
      "colors": [
        "#cbd5e1"
      ]
    }
  ],

  "radii": {
    "signsDistance": 335.6,
    "housesInnerRadius": 145,
    "housesOuterRadius": 276.25,
    "aspectsInnerRadius": 144
  },

  "rings": {
    "inner": {
      "planetsDistance": 240,
      "ticksInnerRadius": 268,
      "ticksOuterRadius": 276,
      "guideAnchorOffset": 14,
      "planetWidth": 30,
      "planetHeight": 30,
      "degreeFontSize": 13,
      "degreeOffset": 26,
      "degreeMinuteSpace": 3,
      "retrogradeFontSize": 10,
      "retrogradeRightGap": 8,
      "retrogradeTopGap": -10,
      "labelDirection": "inward",
      "minSeparationDeg": 9.0
    },
    "outer": {
      "planetsDistance": 447,
      "ticksInnerRadius": 376,
      "ticksOuterRadius": 384,
      "guideAnchorOffset": -14,
      "planetWidth": 28,
      "planetHeight": 28,
      "degreeFontSize": 11,
      "degreeOffset": 24,
      "degreeMinuteSpace": 3,
      "retrogradeFontSize": 9.5,
      "retrogradeRightGap": 8,
      "retrogradeTopGap": -9,
      "labelDirection": "inward",
      "minSeparationDeg": 5.5,
      "showAngleMarkers": true,
      "showHouseTicks": false
    }
  },

  "dimensions": {
    "signWidth": 42,
    "signHeight": 42
  },

  "houses": {
    "showLines": true,
    "strokeWidth": 1.0,
    "axisStrokeWidth": 2.0,
    "color": "#475569",
    "axisColor": "#38bdf8",
    "showNumbers": true,
    "numberColor": "#64748b"
  },

  "aspects": {
    "showLines": true,
    "showAngleLines": false,
    "showNodeLines": false,
    "strokeWidth": 1.4
  }
}
```

---

## Boas práticas de design e manutenção

1. **Transparência nos arquivos base:** `base.svg` e `base-biwheel.svg` devem conter apenas traçados estruturais e réguas de graus, sem retângulos ou círculos opacos cobrindo o centro ou as faixas de signos.
2. **Alinhamento angular dos 12 setores:** ambos os construtores posicionam o centro do signo do Ascendente (do mapa simples ou do mapa interno no bi-wheel) exatamente em `180°` (eixo esquerdo das 9 horas), com divisórias entre signos em `165°` e `195°`, e incrementos em sentido anti-horário. As 12 linhas radiais de divisão dos signos nos arquivos base devem seguir essa mesma geometria.
3. **Espaço da mandala vs. prancha A4:** projete `base.svg` e `base-biwheel.svg` sempre em `viewBox="0 0 1000 1000"` com centro em `(500, 500)`. Ajuste tamanho final e altura na folha A4 exclusivamente por `layout.wheel.scale` e `layout.wheel.centerY`, reservando a metade inferior da folha (`y > 880`) para as grades e matrizes analíticas.
4. **Calibração de sobreposição em stelliums:** caso ocorra acúmulo de planetas no anel interno do bi-wheel, prefira reduzir ligeiramente `planetWidth`/`planetHeight` (para `26`–`30 px`) e `degreeFontSize` (para `11`–`13 px`) em vez de aumentar demais `minSeparationDeg`, evitando deslocamentos angulares distantes da posição real do planeta.
```
