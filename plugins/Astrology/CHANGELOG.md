# Changelog

## [1.2.0] 2026-11-25

### Added
- Modo **Céu de Agora** (`sky_now`) na calculadora pública (`PublicChartController` e `calculator.blade.php`), exibindo posições e aspectos atuais sem casas ou ângulos.
- Cores configuráveis (cabeçalho, fundo, texto e borda) em `AspectGridSvgBuilder`, `ElementsQualitiesSvgBuilder` e `CrossAspectGridSvgBuilder` via `layout.*`.
- Comando `php artisan astrology:sync` (`SyncThemesCommand`) e manifesto `themes/manifest.json` separando temas `public` e `private` (`owner`).
- Métodos de manifesto no `ThemeManager` (`getPublicThemes()`, `getAdminThemes()`, `syncManifest()`), acesso total para `admin` e agrupamento com `<optgroup>` no `<select>`.
- Role `astrologer` e permissão `manage-charts`.

### Changed
- `ChartSvgBuilder::build()` e `BiwheelChartSvgBuilder::build()` agora escalam e centralizam apenas a imagem da mandala (`width`/`height`/`size`/`margin`, padrão `80%` com margem), renderizando textos, ícones e traços em `1:1` (`layout.wheel.scale` opcional).
- `PublicChartController` atualizado para validar temas por usuário e pré-selecionar o tema exclusivo do usuário logado em `resolvePublicTheme()`.
- `.astro-calc-tables-grid` com `:only-child` ocupando `100%` quando apenas uma tabela é exibida.

### Fixed
- Valores padrão de `BiwheelChartSvgBuilder` (`renderOuterAngleMarkers()`, `renderOuterHouseTicks()`, `renderRingPlanets()`, `renderCrossAspectLines()`) ajustados para escala `1:1`.
- Fallback de `ThemeManager::getTheme()` e `getBiwheelTheme()` corrigido para priorizar `'classic'`/temas públicos antes de `reset()`.
- Middlewares do grupo admin em `routes.php` unificados em um único array para evitar sobrescrita de `web`/`auth`.

## [1.1.1] 2026-11-22

### Added
- Roller Picker adicionado em todos os campos de data e hora

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
