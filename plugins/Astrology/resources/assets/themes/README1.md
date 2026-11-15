# Guia de especificação de temas para mapas astrais

Este guia estabelece os parâmetros e as restrições técnicas para a criação e manutenção de temas vetoriais no plugin de astrologia.

---

## Estrutura de diretórios

Cada tema deve ser encapsulado dentro de seu próprio diretório em `public/plugins/astrology/themes/{slug-do-tema}/` ou `Astrology/resources/assets/themes/{slug-do-tema}/`.

```text
meu-tema/
├── theme.json
├── base.svg
├── background.svg (opcional)
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

---

## Ordem de empilhamento vetorial

O motor de renderização compila os elementos na seguinte ordem estrita de profundidade:

```
1. Fundo do canvas (rect com backgroundColor)
2. Imagem decorativa de fundo (assets.background - opcional)
3. Faixas dinâmicas de cores (bands)
4. Mandala estrutural (assets.base - SVG com canal alfa transparente)
5. Glifos dos doze signos (signsDir)
6. Linhas e números das casas astrológicas
7. Linhas centrais de aspectos
8. Tracinhos da régua, glifos planetários e textos de graus
```

---

## Dicionário do manifesto theme.json

### 1. Metadados gerais
* `name` *(string, obrigatório)*: nome legível do tema para seleção na interface administrativa.
* `slug` *(string, obrigatório)*: identificador único correspondente ao nome da pasta do tema.
* `version` *(string)*: versão do manifesto.
* `author` *(string)*: autor ou empresa responsável pelo design.
* `description` *(string)*: resumo técnico do estilo do tema.

### 2. Canvas
* `viewBoxWidth` *(inteiro, padrão: 1000)*: largura da caixa de visualização SVG.
* `viewBoxHeight` *(inteiro, padrão: 1000)*: altura da caixa de visualização SVG.
* `centerX` *(float, padrão: 500)*: coordenada X do centro geométrico da mandala.
* `centerY` *(float, padrão: 500)*: coordenada Y do centro geométrico da mandala.
* `backgroundColor` *(string, padrão: "#ffffff")*: cor de fundo sólida aplicada antes de qualquer ativo. Utilize `"transparent"` para omitir.

### 3. Ativos estáticos (assets)
* `background` *(string|null)*: caminho relativo para a imagem decorativa de fundo (SVG, PNG ou WebP).
* `base` *(string, obrigatório)*: arquivo SVG contendo os traçados geométricos fixos da mandala (réguas, círculos concêntricos e divisões de 30 graus com fundo transparente).
* `signsDir` *(string)*: diretório contendo os glifos dos 12 signos em minúsculo (`aries.svg`, `taurus.svg`, etc.).
* `planetsDir` *(string)*: diretório contendo os glifos dos corpos celestes (`sun.svg`, `moon.svg`, `north_node.svg`, etc.).
* `extension` *(string, padrão: "svg")*: extensão dos arquivos vetoriais de glifos.

### 4. Faixas dinâmicas de cor (bands)
Coleção de anéis concêntricos que giram em consonância com a rotação do Ascendente.

Cada objeto dentro da lista `bands` suporta:
* `name` *(string, obrigatório)*: nome identificador da faixa para rastreabilidade de erros.
* `innerRadius` *(float, obrigatório)*: raio interno da faixa em pixels relativos ao canvas.
* `outerRadius` *(float, obrigatório)*: raio externo da faixa (deve ser estritamente maior que `innerRadius`).
* `opacity` *(float, padrão: 1.0)*: opacidade da faixa (entre `0.0` e `1.0`).
* `eclipticOffset` *(float, padrão: 0.0)*: deslocamento inicial em graus na eclíptica ($0 = 0^\circ$ de Áries, $90 = 0^\circ$ de Câncer).
* `colors` *(array de strings, obrigatório)*: lista de códigos de cor CSS.
  * **Regra de validação estrita:** a quantidade de itens no array deve ser rigorosamente **1, 2, 3, 4, 6, 12 ou 36**. Qualquer outra quantidade lançará exceção de validação.

| Quantidade de cores | Arco por setor | Finalidade astrológica |
| :--- | :--- | :--- |
| 1 | $360^\circ$ | Anel sólido contínuo |
| 2 | $180^\circ$ | Hemisférios |
| 3 | $120^\circ$ | Triplicidades / Elementos condensados |
| 4 | $90^\circ$ | Quadrantes zodiacais |
| 6 | $60^\circ$ | Polaridades / Pares de signos |
| 12 | $30^\circ$ | Signos individuais |
| 36 | $10^\circ$ | Decanatos |

### 5. Raios geométricos (radii)
* `signsDistance` *(float)*: distância do centro até a ancoragem dos glifos dos signos.
* `ticksDistance` *(float)*: raio externo onde se ancoram os tracinhos exatos de posição planetária.
* `ticksLength` *(float)*: comprimento em pixels dos tracinhos exatos.
* `planetsDistance` *(float)*: raio onde orbitam os glifos planetários.
* `housesInnerRadius` *(float)*: início das linhas das cúspides das casas.
* `housesOuterRadius` *(float)*: término das linhas das cúspides das casas.
* `aspectsInnerRadius` *(float)*: raio limite onde terminam as retas centrais de aspectos.

### 6. Casas astrológicas (houses)
* `showLines` *(boolean)*: desenha ou oculta as retas das 12 casas.
* `strokeWidth` *(float)*: espessura das linhas das casas intermediárias.
* `axisStrokeWidth` *(float)*: espessura das retas dos eixos principais (Casas 1, 4, 7 e 10).
* `color` *(string)*: cor das linhas das casas comuns.
* `axisColor` *(string)*: cor de destaque das linhas dos eixos angulares.
* `showNumbers` *(boolean)*: exibe os números de 1 a 12 no meio do setor de cada casa.
* `numberColor` *(string)*: cor do texto dos números das casas.

### 7. Dimensões e tipografia (dimensions)
* `signWidth` e `signHeight` *(float)*: largura e altura do glifo do signo.
* `planetWidth` e `planetHeight` *(float)*: largura e altura do glifo do planeta.
* `planetDegreeFontSize` *(float)*: tamanho da fonte dos graus do planeta (ex.: $18^\circ$).
* `planetDegreeOffset` *(float)*: deslocamento radial entre o centro do planeta e o texto do grau.
* `degreeMinuteSpace` *(float)*: espaçamento radial entre o texto do grau e o texto do minuto.
* `retrogradeFontSize` *(float)*: tamanho da fonte do indicador "R" (retrógrado).
* `retrogradeRightGap` *(float)*: deslocamento horizontal do indicador "R".
* `retrogradeTopGap` *(float)*: deslocamento vertical do indicador "R".

### 8. Aspectos (aspects)
* `showLines` *(boolean)*: desenha as linhas de aspectos no centro.
* `strokeWidth` *(float)*: espessura das retas de aspectos maiores.
* `colors` *(objeto)*: mapeamento de cores hexadecimais para cada tipo de aspecto:
  * `conjunction`, `opposition`, `trine`, `square`, `sextile`, `quincunx`, `semisquare`, `sesquiquadrate`, `semisextile`.

---

## Exemplo completo de theme.json

```json
{
    "name": "Clássico",
    "slug": "classic",
    "version": "1.2",
    "author": "Lunar Base",
    "description": "Tema clássico baseado em vetores de alta precisão com suporte a faixas coloridas e fundo desacoplado.",

    "canvas": {
        "viewBoxWidth": 1000,
        "viewBoxHeight": 1000,
        "centerX": 500,
        "centerY": 500,
        "backgroundColor": "#ffffff"
    },

    "assets": {
        "background": "background.svg",
        "base": "base.svg",
        "planetsDir": "planets/",
        "signsDir": "signs/",
        "extension": "svg"
    },

    "bands": [
        {
            "name": "sign_segments",
            "innerRadius": 340,
            "outerRadius": 445,
            "opacity": 0.25,
            "eclipticOffset": 0,
            "colors": [
                "#f87171", "#fb923c", "#facc15", "#4ade80",
                "#2dd4bf", "#38bdf8", "#818cf8", "#c084fc",
                "#f472b6", "#94a3b8", "#a3e635", "#fb7185"
            ]
        },
        {
            "name": "quadrant_shading",
            "innerRadius": 145,
            "outerRadius": 320,
            "opacity": 0.06,
            "eclipticOffset": 0,
            "colors": [
                "#fee2e2",
                "#fef3c7",
                "#dcfce7",
                "#e0e7ff"
            ]
        }
    ],

    "radii": {
        "signsDistance": 392,
        "ticksDistance": 320,
        "ticksLength": 8,
        "planetsDistance": 270,
        "housesInnerRadius": 145,
        "housesOuterRadius": 340,
        "aspectsInnerRadius": 144
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

    "dimensions": {
        "signWidth": 42,
        "signHeight": 42,
        "planetWidth": 32,
        "planetHeight": 32,
        "planetDegreeFontSize": 13,
        "planetDegreeOffset": 28,
        "retrogradeFontSize": 10,
        "retrogradeRightGap": 8,
        "retrogradeTopGap": -10,
        "degreeMinuteSpace": 4
    },

    "aspects": {
        "showLines": true,
        "strokeWidth": 1.5,
        "colors": {
            "conjunction": "#38bdf8",
            "opposition": "#ef4444",
            "trine": "#10b981",
            "square": "#f87171",
            "sextile": "#60a5fa",
            "quincunx": "#a855f7",
            "semisquare": "#fb923c",
            "sesquiquadrate": "#fb923c",
            "semisextile": "#94a3b8"
        }
    }
}
```

---

## Boas práticas de design e manutenção

1. **Transparência na imagem base:** certifique-se de que o arquivo `base.svg` não possua tags `<rect>` de preenchimento ou nós de fundo cobrindo o centro ou a faixa zodiacal. O arquivo deve conter unicamente os traçados das linhas e réguas.
2. **Proporção 1:1:** as dimensões do `viewBox` devem ser preferencialmente quadradas ($1000 \times 1000\text{ px}$ com centro em $500, 500$) para manter simetria radial perfeita em todos os cálculos trigonométricos.
3. **Calibração de fontes:** para evitar colisões de texto em mapas com acúmulos planetários acentuados (*stelliums*), mantenha `planetDegreeFontSize` entre $11$ e $14\text{ px}$.
