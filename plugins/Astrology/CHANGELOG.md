# Changelog

## [1.1.0] 2026-11-21

### Added
- Suporte a `<tspan>` com deslocamento relativo (`dy` em `em`) e escala proporcional de fonte (`0.65 × fontSize`) nos cabeçalhos do `ChartSvgBuilder` e `BiwheelChartSvgBuilder` (`renderHeaderBlock`).
- Suporte completo em `BiwheelChartSvgBuilder::renderBiwheelHeader()` às propriedades de `layout.header` (`fontSize`, `color`, `innerAlign`, `outerAlign`, `innerDotColor`, `outerDotColor`) com herança automática de `layout.chartInfo` e `rings.*.tickColor`.
- Configuração `publicTheme` em `plugin.json` (`configurable`) e método `resolvePublicTheme()` no `PublicChartController` para definir o tema padrão da calculadora pública via `pluginSettings`.
- Documentação completa das novas propriedades de `theme.json` e `theme-biwheel.json` no `Astrology/resources/assets/themes/README.md`.

### Changed
- `ChartSvgBuilder::renderHeader()` atualizado para ler `layout.chartInfo` (`x`, `y`, `align`, `fontSize`, `color`) com fallback para as chaves legadas (`infoX`, `infoY`, `infoAlign`).

### Fixed
- Corrigida precedência de operador em `RendersMandalaPrimitives::renderHouseLines()` nos casts de `numberSize` e `degSize` (`(float) ($theme['houses']['numberSize'] ?? 10.5)`), eliminando o erro `Undefined array key "numberSize"` em temas que não definem essas chaves.
- Adicionado escape XML (`htmlspecialchars`) no `$chart->timezone` dentro do cabeçalho do `ChartSvgBuilder`.
