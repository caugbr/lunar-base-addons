# Guia de especificação de temas para mapas astrológicos

Este guia estabelece os parâmetros, as regras de herança e as restrições técnicas para criação e manutenção de temas vetoriais no plugin de astrologia, abrangendo mapas simples (`theme.json`) e mapas em sobreposição (`theme-biwheel.json`).

---

## Estrutura de diretórios

Cada tema deve ser encapsulado dentro de seu próprio diretório em `public/plugins/astrology/themes/{slug-do-tema}/` ou `Astrology/resources/assets/themes/{slug-do-tema}/`.

```text
meu-tema/
├── theme.json
├── theme-biwheel.json (opcional — habilita o tema para sobreposição de dois mapas)
├── base.svg
├── base-biwheel.svg (padrão no bi-wheel caso assets.base não seja sobrescrito)
├── background.jpg (opcional — svg, png, jpg ou webp)
├── logo.png (opcional — svg, png, jpg ou webp)
├── signs/
│   ├── aries.svg (ou .png, conforme assets.extension)
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

Tanto o construtor de mapa simples (`ChartSvgBuilder`) quanto o de roda dupla (`BiwheelChartSvgBuilder`) compilam o SVG da prancha seguindo esta ordem de profundidade:

```text
1. Fundo do canvas (rect sólido com canvas.backgroundColor)
2. Imagem decorativa de fundo (assets.background — opcional)
3. Cabeçalho da prancha (informações do(s) mapa(s) e assets.logo)
4. Grupo #mandala-wheel (espaço interno 1000x1000 escalado por layout.wheel):
   4.1. Faixas dinâmicas de cores (bands)
   4.2. Mandala estrutural (assets.base — base.svg ou base-biwheel.svg)
   4.3. Glifos dos doze signos (signsDir)
   4.4. Linhas, graus das cúspides e números das 12 casas (mapa simples ou mapa interno do bi-wheel)
   4.5. Marcadores de eixos angulares internos (axes.showMarkers — quando ativo)
   4.6. Linhas centrais de aspectos (aspectos internos ou aspectos cruzados)
   4.7. Órbita(s) planetária(s):
        - Mapa simples: régua de ticks, linhas-guia, glifos, graus e minutos
        - Bi-wheel: anel interno (rings.inner), anel externo (rings.outer), marcadores de ângulos externos (angleMarkers) e ticks opcionais de casas externas
5. Blocos analíticos inferiores (opcionais):
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
* `modules.chartInfo` *(boolean, padrão: false)*: exibe os dados de nascimento e localização no cabeçalho.
* `modules.logo` *(boolean, padrão: false)*: renderiza a imagem definida em `assets.logo` no cabeçalho.
* `modules.footerText` *(boolean, padrão: false)*: exibe o texto configurado em `layout.footer` na base da prancha.

### 3. Layout da prancha (`layout`)
O espaço interno da mandala opera sempre em `1000 x 1000` (centro em `500, 500`) e é posicionado na prancha através de `layout.wheel`.

#### Mandala (`layout.wheel`)
* `centerX` *(float, padrão: 500)*: coordenada X do centro da mandala na prancha.
* `centerY` *(float, padrão: 530)*: coordenada Y do centro da mandala na prancha.
* `scale` *(float, padrão: 0.85)*: fator de escala aplicado ao grupo `#mandala-wheel`.

#### Informações do mapa no cabeçalho (`layout.chartInfo`)
* `x` *(float ou string com `%`, padrão: 50)*: posição X de ancoragem do bloco (ex.: `50` ou `"50%"`).
* `y` *(float, padrão: 45)*: posição Y da linha de base do título (1ª linha).
* `align` *(string, padrão: "left")*: alinhamento horizontal do texto (`"left"` / `"start"`, `"center"` / `"middle"`, `"right"` / `"end"`).
* `fontSize` *(float, padrão: 16)*: tamanho da fonte do título (1ª linha). As linhas 2 e 3 escalam automaticamente para `0.65 × fontSize` usando `dy` proporcional (`1.7em` e `1.4em`).
* `color` *(string, padrão: "#1e293b")*: cor do texto (linhas secundárias recebem `fill-opacity="0.8"`).
* *(Chaves legadas ainda aceitas como fallback)*: `layout.infoX`, `layout.infoY`, `layout.infoAlign`.

#### Logotipo (`layout.logo`)
* `x` *(float, `"center"` ou `"%"`, padrão: 770)*: posição horizontal de referência do logo.
* `y` *(float ou `"%"`, padrão: 35)*: distância vertical a partir do topo ou da base (conforme `yFrom`).
* `yFrom` *(string: `"top"` | `"bottom"`, padrão: `"top"`)*: define se `y` é contado a partir do topo ou da borda inferior da prancha (como `bottom` no CSS).
* `width` *(float, padrão: 180)*: largura da caixa do logotipo.
* `height` *(float, padrão: 75)*: altura da caixa do logotipo.
* `align` *(string, padrão: `"default"`)*: desloca a caixa em relação a `x` e ajusta `preserveAspectRatio` (`"center"` / `"middle"` centraliza em `x - width/2`; `"right"` / `"end"` alinha à direita em `x - width`; `"left"` / `"start"` inicia em `x`).

#### Rodapé (`layout.footer`)
* `text` *(string, padrão: "Lunar Base Astrology")*: texto centralizado no rodapé quando `modules.footerText` está ativo.
* `fontSize` *(float, padrão: 12)*: tamanho da fonte do rodapé.
* `color` *(string, padrão: "#94a3b8")*: cor do texto do rodapé.
* `bottomDistance` *(float, padrão: 52)*: distância em unidades do `viewBox` entre a borda inferior da prancha e o texto (`viewBoxHeight - bottomDistance`).

#### Blocos analíticos inferiores (`layout.aspectGrid` e `layout.elementsQualities`)
* `layout.aspectGrid` *(objeto ou `false`)*: defina `false` para ocultar a grade triangular de aspectos no SVG, ou informe `x` (padrão `50`), `y` (padrão `990`) e `cellSize` (padrão `24`).
* `layout.elementsQualities` *(objeto ou `false`)*: defina `false` para ocultar a matriz 4x3 de elementos e qualidades no SVG, ou informe `x` (padrão `620`) e `y` (padrão `990`).

### 4. Tipografia (`typography`)
* `googleFontsUrl` *(string|null)*: URL CSS (ex.: Google Fonts) inserida via `@import` dentro de `<style>` no `<defs>` do SVG.
* `fontFamily` *(string, padrão: "sans-serif")*: fonte principal (`body`), usada nos subtítulos do cabeçalho, graus/minutos planetários, números das casas e rodapé.
* `headingFontFamily` *(string, padrão: igual a `fontFamily`)*: fonte de destaque (`heading`), usada no título do cabeçalho e nos marcadores dos eixos (`ASC`, `DSC`, `MC`, `FC`).
* `monoFontFamily` *(string, padrão: "monospace")*: fonte monoespaçada usada nos graus e orbes da grade triangular de aspectos no SVG.

### 5. Ativos estáticos (`assets`)
* `background` *(string|null)*: caminho relativo para imagem decorativa de fundo (`svg`, `png`, `jpg` ou `webp`), aplicada em `0, 0` com `viewBoxWidth × viewBoxHeight`.
* `base` *(string, obrigatório)*: arquivo SVG contendo os traçados fixos da mandala (`base.svg`) com fundo transparente.
* `logo` *(string|null)*: arquivo de logotipo posicionado via `layout.logo` quando `modules.logo` está ativo.
* `signsDir` *(string, padrão: "signs/")*: diretório com os glifos dos 12 signos em minúsculo (`aries.svg`, `taurus.svg`, etc.).
* `planetsDir` *(string, padrão: "planets/")*: diretório com os glifos dos corpos celestes (`sun.svg`, `moon.svg`, `north_node.svg`, etc.).
* `extension` *(string, padrão: "svg")*: extensão dos arquivos de glifos (`svg`, `png`, `jpg`, `webp`).

### 6. Faixas dinâmicas de cor (`bands`)
Coleção de anéis concêntricos desenhados no espaço `1000 x 1000` da mandala, girando conforme o signo do Ascendente.

Cada objeto dentro de `bands` aceita:
* `name` *(string, obrigatório)*: identificador da faixa para rastreabilidade em mensagens de erro.
* `innerRadius` *(float, obrigatório)*: raio interno relativo ao espaço `1000 x 1000`.
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

### 7. Signos e raios geométricos (`signs` e `radii`)
* `signs.alignToCenter` *(boolean, padrão: false)*: quando `true`, rotaciona cada glifo de signo (`90 - ângulo`) para apontar radialmente para o centro da mandala.

Todos os raios abaixo são medidos a partir de `(500, 500)` dentro do grupo `#mandala-wheel`:
* `radii.signsDistance` *(float, padrão: 390)*: raio onde são ancorados os glifos dos 12 signos.
* `radii.ticksDistance` *(float, padrão: 325)*: raio externo dos tracinhos exatos de posição planetária.
* `radii.ticksLength` *(float, padrão: 8)*: comprimento dos tracinhos exatos (`ticksDistance - ticksLength` até `ticksDistance`).
* `radii.planetsDistance` *(float, padrão: 290)*: raio onde orbitam os glifos planetários.
* `radii.housesInnerRadius` *(float, padrão: 145)*: raio inicial das linhas das cúspides das casas (os números das casas ficam em `housesInnerRadius + 18`).
* `radii.housesOuterRadius` *(float, padrão: 340)*: raio final das linhas das cúspides das casas (os graus das cúspides ficam em `housesOuterRadius - 12`).
* `radii.aspectsInnerRadius` *(float, padrão: 144)*: raio limite onde se conectam as linhas centrais de aspectos.

### 8. Casas astrológicas (`houses`)
* `showLines` *(boolean)*: desenha ou oculta as retas das 12 casas.
* `strokeWidth` *(float, padrão: 1.0)*: espessura das linhas das casas intermediárias.
* `axisStrokeWidth` *(float, padrão: 2.0)*: espessura das retas dos eixos principais (Casas 1, 4, 7 e 10).
* `color` *(string, padrão: "#475569")*: cor das linhas das casas comuns e também usada como cor de borda das grades SVG.
* `axisColor` *(string, padrão: "#38bdf8")*: cor de destaque dos eixos angulares (Casas 1, 4, 7 e 10).
* `showNumbers` *(boolean)*: exibe os números de 1 a 12 no interior do setor de cada casa.
* `numberColor` *(string, padrão: "#64748b")*: cor dos números das casas.
* `numberSize` *(float, padrão: 10.5)*: tamanho da fonte dos números das casas.
* `degSize` *(float, padrão: 8.5)*: tamanho da fonte dos graus das cúspides (ex.: `15°`).

### 9. Marcadores de eixos (`axes`)
Controla os marcadores de `Ascendente`, `Descendente`, `Meio do Céu` e `Fundo do Céu`:
* `showMarkers` *(boolean, padrão: false)*: exibe ou oculta os marcadores.
* `distance` *(float, padrão: 468)*: raio onde o texto ou imagem do marcador é posicionado.
* `mode` *(string: `"auto"` | `"image"` | `"text"`, padrão: `"auto"`)*: `"auto"` usa imagem se existir e texto caso contrário; `"image"` só renderiza se a imagem existir; `"text"` força sempre texto.
* `imageWidth` / `imageHeight` *(float, padrão: 28)*: dimensões da imagem quando usada.
* `fontSize` *(float, padrão: 11)*: tamanho da fonte do texto do marcador.
* `fontWeight` *(string, padrão: "bold")*: peso da fonte (`normal`, `bold`, `600`, etc.).
* `color` *(string, padrão: herda `houses.axisColor`)*: cor do texto (e do tracinho, se `tickColor` não for informado).
* `showTicks` *(boolean, padrão: false)*: desenha um tracinho exato na direção do eixo.
* `ticksInnerRadius` *(float, padrão: `distance - 20`)*: início do tracinho do eixo.
* `ticksOuterRadius` *(float, padrão: `distance - 12`)*: fim do tracinho do eixo.
* `tickColor` *(string, opcional)*: cor específica do tracinho do eixo.
* `tickStrokeWidth` *(float, padrão: 2.0)*: espessura do tracinho do eixo.
* `labels` *(objeto com chaves `asc`, `dsc`, `mc`, `ic`)*: rótulos exibidos (padrão `"ASC"`, `"DSC"`, `"MC"`, `"FC"`). Defina `null` ou `""` para ocultar um eixo específico.
* `images` *(objeto com chaves `asc`, `dsc`, `mc`, `ic`)*: caminho relativo à pasta do tema para usar uma imagem no lugar do texto (ex.: `"axes/asc.png"`).

### 10. Planetas, dimensões e régua de pontos (`planets`, `dimensions` e `ticks`)
* `planets.showDegrees` *(boolean, padrão: true)*: exibe ou oculta os rótulos de grau e minuto junto aos planetas.
* `planets.showRetrograde` *(boolean, padrão: true)*: exibe ou oculta o indicador `"R"` vermelho nos planetas retrógrados.
* `dimensions.signWidth` e `signHeight` *(float, padrão: 48)*: dimensões do glifo de signo.
* `dimensions.planetWidth` e `planetHeight` *(float, padrão: 38)*: dimensões do glifo de planeta.
* `dimensions.planetDegreeFontSize` *(float, padrão: 14)*: tamanho da fonte do grau do planeta (os minutos usam `max(6.5, planetDegreeFontSize - 2)`).
* `dimensions.planetDegreeOffset` *(float)*: deslocamento radial para dentro entre o centro do planeta e o texto do grau (`planetsDistance - offset`).
* `dimensions.degreeMinuteSpace` *(float, padrão: 4)*: espaçamento radial entre o texto do grau e o texto do minuto.
* `dimensions.retrogradeFontSize` *(float, padrão: 10)*: tamanho da fonte do indicador `"R"`.
* `dimensions.retrogradeRightGap` e `retrogradeTopGap` *(float, padrão: 8 e -10)*: deslocamento X e Y do indicador `"R"` relativo ao centro do planeta.
* `ticks.color`, `ticks.strokeWidth` *(opcionais, padrão: `"#0284c7"` e `1.5`)*: cor e espessura dos traços exatos da posição planetária.
* `ticks.guideColor`, `ticks.guideWidth`, `ticks.guideDash` *(opcionais, padrão: `"#94a3b8"`, `0.75` e `"2,2"`)*: estilo da linha tracejada que liga o traço exato ao glifo quando há desvio anti-colisão.

### 11. Aspectos (`aspects`)
* `showLines` *(boolean)*: desenha ou oculta as linhas de aspectos no miolo da mandala.
* `showAngleLines` *(boolean)*: inclui ou oculta linhas de aspectos envolvendo `Ascendant`, `MC`, `Descendant` e `Imum Coeli` dentro do círculo central.
* `showNodeLines` *(boolean)*: inclui ou oculta linhas de aspectos envolvendo nodos lunares dentro do círculo central.
* `strokeWidth` *(float)*: espessura das retas de aspectos (aspectos menores são tracejados com `3,3` e opacidade reduzida).
* `colors` *(objeto)*: cores hexadecimais por tipo: `conjunction`, `opposition`, `trine`, `square`, `sextile`, `quincunx`, `semisquare`, `sesquiquadrate`, `semisextile`.

---

## Especificação do manifesto `theme-biwheel.json` (roda dupla)

O manifesto `theme-biwheel.json` configura a renderização de dois conjuntos de posições na mesma mandala (`BiwheelChartSvgBuilder` e `CrossAspectGridSvgBuilder`), mantendo o mapa simples (`theme.json`) completamente intacto.

### Regras de herança com o `theme.json`

Ao carregar um tema via `ThemeManager::getBiwheelTheme($slug)`:
1. O arquivo `theme.json` do mesmo diretório atua como base. Não é necessário repetir no `theme-biwheel.json` configurações compartilhadas como `canvas`, `modules`, `typography`, `layout.footer`, `layout.logo`, `assets.planetsDir`, `assets.signsDir`, `assets.extension`, `houses`, `planets`, `signs` ou `aspects.colors`, a menos que queira sobrescrevê-las.
2. Mapas associativos (`canvas`, `modules`, `layout`, `assets`, `radii`, `houses`, `dimensions`, `aspects`, `rings`, `planets`, `typography`, `axes`, `signs`) sofrem mesclagem recursiva por chave (`array_replace_recursive`) sobre o tema base.
3. A chave `bands` é **substituída integralmente** quando declarada no `theme-biwheel.json` (nunca mesclada índice por índice com as faixas do mapa simples).

### Blocos específicos do `theme-biwheel.json`

#### 1. Cabeçalho duplo e grade retangular (`layout`)
* `layout.header.innerX` *(float, `"center"` ou `"%"`, padrão: 50)*: coordenada X do bloco de título/subtítulo do mapa interno (herda `chartInfo.x` / `infoX` se omitido).
* `layout.header.outerX` *(float, `"center"` ou `"%"`, padrão: 430)*: coordenada X do bloco de título/subtítulo do mapa externo (herda `chartInfo.x` / `infoX` se omitido).
* `layout.header.y` *(float ou `"%"`, padrão: 45)*: coordenada Y base compartilhada caso `innerY`/`outerY` não sejam informados (herda `chartInfo.y` / `infoY` se omitido).
* `layout.header.innerY` / `outerY` *(float ou `"%"`)*: coordenadas Y individuais de cada bloco. Quando `innerX == outerX` e `outerY` não é informado, o bloco externo desce automaticamente `fontSize * 3.5`.
* `layout.header.align` *(string)*: alinhamento padrão dos dois blocos (`left`, `center`, `right` — herda `chartInfo.align` / `infoAlign`).
* `layout.header.innerAlign` / `outerAlign` *(string)*: alinhamentos individuais para cada bloco.
* `layout.header.fontSize` *(float)*: tamanho da fonte do título no bi-wheel (herda `chartInfo.fontSize` se omitido; subtítulos usam `0.65 × fontSize` e `dy` proporcional).
* `layout.header.color` *(string)*: cor dos textos no bi-wheel (herda `chartInfo.color` se omitido).
* `layout.header.innerDotColor` / `outerDotColor` *(string)*: cor do marcador (`●`) de cada bloco (se omitidos, herdam `rings.inner.tickColor` e `rings.outer.tickColor`, ou `#0284c7` / `#db2777`).
* `layout.crossAspectGrid` *(objeto ou `false`)*: defina `false` para ocultar a matriz retangular N x M e o painel lateral no SVG, ou configure:
  * `x` *(float, padrão: 50)*: origem X da matriz.
  * `y` *(float, padrão: 885)*: origem Y da matriz.
  * `cellSize` *(float, padrão: 28)*: tamanho alvo das células (reduzido automaticamente para caber na largura útil de `900 px`).
  * `summaryWidth` *(float, padrão: 165)*: largura da coluna esquerda com as posições do mapa interno.

#### 2. Configuração independente por anel (`rings.inner` e `rings.outer`)
Substitui os raios únicos de planetas do mapa simples por dois blocos independentes, permitindo calibração distinta para a órbita interna (menor circunferência) e a coroa externa (maior circunferência):

* `showDegrees` / `showRetrograde` *(boolean, opcionais)*: sobrescrevem `planets.showDegrees` / `planets.showRetrograde` apenas nesse anel.
* `planetsDistance` *(float)*: raio da órbita onde ficam os glifos do anel.
* `ticksInnerRadius` *(float)*: raio interno do tracinho exato de posição.
* `ticksOuterRadius` *(float)*: raio externo do tracinho exato de posição.
* `tickColor`, `tickWidth` *(opcionais)*: cor e espessura do tracinho exato do anel (padrão `#0284c7` no interno e `#db2777` no externo).
* `guideAnchorOffset` *(float com sinal)*: deslocamento radial a partir de `planetsDistance` onde a linha-guia anti-colisão ancora no planeta (positivo quando os ticks estão por fora da órbita do planeta; negativo quando estão por dentro).
* `planetWidth`, `planetHeight` *(float)*: dimensões dos glifos de planeta nesse anel.
* `degreeFontSize`, `degreeOffset`, `degreeMinuteSpace` *(float)*: tipografia e espaçamento radial dos textos de grau e minuto (`minFontSize = max(6.5, degreeFontSize - 2)`).
* `retrogradeFontSize`, `retrogradeRightGap`, `retrogradeTopGap` *(float)*: posição e tamanho do `"R"` de retrogradação.
* `labelDirection` *(string: `"inward"` | `"outward"`, padrão: `"inward"`)*: define se os textos de grau e minuto são posicionados em direção ao centro (`"inward"`) ou em direção à borda externa (`"outward"`) a partir do glifo do planeta.
* `minSeparationDeg` *(float)*: separação angular mínima (em graus) aplicada pelo algoritmo anti-colisão desse anel. Anéis internos comprimidos exigem valores maiores (ex.: `8.5°`–`9.5°`) do que coroas externas amplas (ex.: `5.0°`–`6.0°`).
* `showAngleMarkers` *(boolean, exclusivo de `rings.outer`)*: plota marcadores dos ângulos do mapa externo na borda da coroa externa.
* `angleMarkers` *(objeto, exclusivo de `rings.outer`)*: aceita todas as chaves da seção `axes` (`distance`, `mode`, `fontSize`, `fontWeight`, `color`, `showTicks`, `ticksInnerRadius`, `ticksOuterRadius`, `tickStrokeWidth`, `labels`, `images`). Padrão de `labels`: `asc: "AC"`, `mc: "MC"`, `dsc: null`, `ic: null`.
* `showHouseTicks` *(boolean, exclusivo de `rings.outer`)*: plota traços curtos e números discretos das cúspides do mapa externo na borda da coroa externa (`r = 466..481`; recomenda-se `false` por padrão para evitar ruído visual).

---

## Exemplo completo de `theme-biwheel.json`

Calibrado para o `base-biwheel.svg` com círculo externo de raio `470` (`940` de diâmetro), faixa de signos entre `296.25` e `375`, mapa interno entre `145` e `276.25` e coroa externa entre `400` e `470`:

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
    "numberColor": "#64748b",
    "numberSize": 10.5,
    "degSize": 8.5
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
3. **Espaço da mandala vs. prancha:** projete `base.svg` e `base-biwheel.svg` sempre em `viewBox="0 0 1000 1000"` com centro em `(500, 500)`. Ajuste tamanho final e posição vertical na folha exclusivamente por `layout.wheel.scale`, `layout.wheel.centerX` e `layout.wheel.centerY`. Caso a grade de aspectos esteja ativa, reserve a metade inferior (`y > 880`) para ela.
4. **Calibração de sobreposição em stelliums:** caso ocorra acúmulo de planetas no anel interno do bi-wheel, prefira reduzir ligeiramente `planetWidth`/`planetHeight` (para `26`–`30`) e `degreeFontSize` (para `11`–`13`) em vez de aumentar demais `minSeparationDeg`, evitando deslocamentos angulares distantes da posição real do planeta.
5. **Cuidado ao usar `theme-biwheel.json` sobre o `theme.json`:** se o `theme.json` usar uma `base.svg` com faixa zodiacal em outro raio (ex.: `330..493` com `signsDistance: 410`) e `theme-biwheel.json` reutilizar `base.svg`, atualize também `radii`, `bands` e `rings` no `theme-biwheel.json` para acompanhar a mesma escala da roda base — além de ajustar `layout.wheel.scale` caso o anel externo ultrapasse `r = 500`.

