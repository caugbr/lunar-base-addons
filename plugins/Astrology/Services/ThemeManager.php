<?php

namespace Plugins\Astrology\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ThemeManager
{
    protected const CACHE_KEY = 'astrology_available_themes';
    protected const CACHE_BIWHEEL_KEY = 'astrology_available_biwheel_themes';

    protected static ?array $memoryCache = null;
    protected static ?array $biwheelMemoryCache = null;

    public static function getThemeDirectory(string $slug): ?string
    {
        $theme = self::getTheme($slug);

        return $theme['path'] ?? null;
    }

    public static function getThemes(): array
    {
        if (config('app.debug')) {
            self::$memoryCache = self::loadThemesFromDisk();

            return self::$memoryCache;
        }

        if (self::$memoryCache !== null) {
            return self::$memoryCache;
        }

        self::$memoryCache = Cache::rememberForever(self::CACHE_KEY, function () {
            return self::loadThemesFromDisk();
        });

        return self::$memoryCache;
    }

    public static function getBiwheelThemes(): array
    {
        if (config('app.debug')) {
            self::$biwheelMemoryCache = self::loadBiwheelThemesFromDisk();

            return self::$biwheelMemoryCache;
        }

        if (self::$biwheelMemoryCache !== null) {
            return self::$biwheelMemoryCache;
        }

        self::$biwheelMemoryCache = Cache::rememberForever(self::CACHE_BIWHEEL_KEY, function () {
            return self::loadBiwheelThemesFromDisk();
        });

        return self::$biwheelMemoryCache;
    }

    public static function hasBiwheelTheme(string $slug = 'classic'): bool
    {
        $themes = self::getBiwheelThemes();

        return isset($themes[$slug]);
    }

    public static function getTheme(string $slug = 'classic'): array
    {
        $themes = self::getThemes();

        if (isset($themes[$slug])) {
            return $themes[$slug];
        }

        return reset($themes) ?: self::getDefaultFallbackManifest($slug);
    }

    public static function getBiwheelTheme(string $slug = 'classic'): array
    {
        $biwheelThemes = self::getBiwheelThemes();

        if (isset($biwheelThemes[$slug])) {
            return $biwheelThemes[$slug];
        }

        $first = reset($biwheelThemes);
        if ($first) {
            return $first;
        }

        return self::mergeBiwheelManifest(
            self::getTheme($slug),
            self::getDefaultBiwheelFallbackManifest($slug)
        );
    }

    public static function clearCache(): void
    {
        self::$memoryCache = null;
        self::$biwheelMemoryCache = null;
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_BIWHEEL_KEY);
    }

    protected static function getThemeSearchDirectories(): array
    {
        return [
            public_path('plugins/astrology/themes'),
            dirname(__DIR__) . '/resources/assets/themes',
        ];
    }

    protected static function loadThemesFromDisk(): array
    {
        $themes = [];

        foreach (self::getThemeSearchDirectories() as $basePath) {
            if (!File::isDirectory($basePath)) {
                continue;
            }

            foreach (File::directories($basePath) as $dir) {
                $slug = basename($dir);
                $manifestPath = $dir . '/theme.json';

                if (!isset($themes[$slug]) && File::exists($manifestPath)) {
                    $manifest = json_decode(File::get($manifestPath), true);

                    if (is_array($manifest)) {
                        $manifest['slug'] = $slug;
                        $manifest['path'] = $dir;
                        $themes[$slug] = $manifest;
                    }
                }
            }
        }

        return $themes;
    }

    protected static function loadBiwheelThemesFromDisk(): array
    {
        $baseThemes = self::getThemes();
        $biwheelThemes = [];

        foreach (self::getThemeSearchDirectories() as $basePath) {
            if (!File::isDirectory($basePath)) {
                continue;
            }

            foreach (File::directories($basePath) as $dir) {
                $slug = basename($dir);
                $biwheelPath = $dir . '/theme-biwheel.json';

                if (!isset($biwheelThemes[$slug]) && File::exists($biwheelPath)) {
                    $biwheelManifest = json_decode(File::get($biwheelPath), true);

                    if (is_array($biwheelManifest)) {
                        $base = $baseThemes[$slug] ?? self::getDefaultFallbackManifest($slug);
                        $merged = self::mergeBiwheelManifest($base, $biwheelManifest);
                        $merged['slug'] = $slug;
                        $merged['path'] = $dir;
                        $biwheelThemes[$slug] = $merged;
                    }
                }
            }
        }

        return $biwheelThemes;
    }

    protected static function mergeBiwheelManifest(array $baseTheme, array $biwheelManifest): array
    {
        $merged = $baseTheme;

        foreach (['name', 'slug', 'version', 'author', 'description'] as $metaKey) {
            if (isset($biwheelManifest[$metaKey])) {
                $merged[$metaKey] = $biwheelManifest[$metaKey];
            }
        }

        foreach (['canvas', 'modules', 'assets', 'radii', 'houses', 'dimensions', 'aspects', 'rings'] as $assocKey) {
            if (isset($biwheelManifest[$assocKey]) && is_array($biwheelManifest[$assocKey])) {
                $baseSection = $merged[$assocKey] ?? [];
                $merged[$assocKey] = array_replace_recursive($baseSection, $biwheelManifest[$assocKey]);
            }
        }

        if (isset($biwheelManifest['layout']) && is_array($biwheelManifest['layout'])) {
            $merged['layout'] = array_replace_recursive($merged['layout'] ?? [], $biwheelManifest['layout']);
        }

        if (array_key_exists('bands', $biwheelManifest) && is_array($biwheelManifest['bands'])) {
            $merged['bands'] = $biwheelManifest['bands'];
        }

        return $merged;
    }

    protected static function getDefaultFallbackManifest(string $slug): array
    {
        return [
            'name' => 'Clássico Fallback',
            'slug' => $slug,
            'path' => dirname(__DIR__) . "/resources/assets/themes/{$slug}",
            'canvas' => [
                'viewBoxWidth' => 1000,
                'viewBoxHeight' => 1000,
                'centerX' => 500,
                'centerY' => 500,
                'backgroundColor' => '#ffffff',
            ],
            'assets' => [
                'background' => null,
                'base' => 'base.svg',
                'planetsDir' => 'planets/',
                'signsDir' => 'signs/',
                'extension' => 'svg',
            ],
            'bands' => [],
            'radii' => [
                'signsDistance' => 415,
                'ticksDistance' => 365,
                'ticksLength' => 8,
                'planetsDistance' => 270,
                'housesInnerRadius' => 170,
                'housesOuterRadius' => 365,
                'aspectsInnerRadius' => 170,
            ],
            'houses' => [
                'showLines' => true,
                'strokeWidth' => 1.0,
                'axisStrokeWidth' => 2.0,
                'color' => '#475569',
                'axisColor' => '#0284c7',
                'showNumbers' => true,
                'numberColor' => '#64748b',
            ],
            'dimensions' => [
                'signWidth' => 34,
                'signHeight' => 34,
                'planetWidth' => 28,
                'planetHeight' => 28,
            ],
            'aspects' => [
                'showLines' => true,
                'showAngleLines' => false,
                'showNodeLines' => false,
                'strokeWidth' => 1.5,
                'colors' => [
                    'conjunction' => '#0284c7',
                    'opposition' => '#ef4444',
                    'trine' => '#10b981',
                    'square' => '#f87171',
                    'sextile' => '#60a5fa',
                    'quincunx' => '#a855f7',
                    'semisquare' => '#fb923c',
                    'sesquiquadrate' => '#fb923c',
                    'semisextile' => '#94a3b8',
                ],
            ],
        ];
    }

    protected static function getDefaultBiwheelFallbackManifest(string $slug): array
    {
        return [
            'name' => 'Clássico Bi-Wheel Fallback',
            'slug' => $slug,
            'canvas' => [
                'viewBoxWidth' => 1000,
                'viewBoxHeight' => 1414,
                'backgroundColor' => '#ffffff',
            ],
            'layout' => [
                'wheel' => ['centerX' => 500, 'centerY' => 475, 'scale' => 0.80],
                'header' => ['innerX' => 50, 'outerX' => 440, 'y' => 40],
                'crossAspectGrid' => ['x' => 50, 'y' => 885, 'cellSize' => 28, 'summaryWidth' => 165],
            ],
            'assets' => [
                'base' => 'base-biwheel.svg',
                'background' => null,
            ],
            'bands' => [],
            'radii' => [
                'signsDistance' => 335.6,
                'housesInnerRadius' => 145,
                'housesOuterRadius' => 276.25,
                'aspectsInnerRadius' => 144,
            ],
            'rings' => [
                'inner' => [
                    'planetsDistance' => 240,
                    'ticksInnerRadius' => 268,
                    'ticksOuterRadius' => 276,
                    'guideAnchorOffset' => 14,
                    'planetWidth' => 30,
                    'planetHeight' => 30,
                    'degreeFontSize' => 13,
                    'degreeOffset' => 26,
                    'degreeMinuteSpace' => 3,
                    'retrogradeFontSize' => 10,
                    'retrogradeRightGap' => 8,
                    'retrogradeTopGap' => -10,
                    'labelDirection' => 'inward',
                    'minSeparationDeg' => 9.0,
                ],
                'outer' => [
                    'planetsDistance' => 447,
                    'ticksInnerRadius' => 376,
                    'ticksOuterRadius' => 384,
                    'guideAnchorOffset' => -14,
                    'planetWidth' => 28,
                    'planetHeight' => 28,
                    'degreeFontSize' => 11,
                    'degreeOffset' => 24,
                    'degreeMinuteSpace' => 3,
                    'retrogradeFontSize' => 9.5,
                    'retrogradeRightGap' => 8,
                    'retrogradeTopGap' => -9,
                    'labelDirection' => 'inward',
                    'minSeparationDeg' => 5.5,
                    'showAngleMarkers' => true,
                    'showHouseTicks' => false,
                ],
            ],
        ];
    }

    public static function getSignData(string $signName, string $themeSlug = 'classic'): array
    {
        $theme = self::getTheme($themeSlug);
        $themeDir = self::getThemeDirectory($theme['slug']) ?? ($theme['path'] ?? '');

        $cleanName = strtolower(trim($signName));
        $ext = $theme['assets']['extension'] ?? 'svg';

        $filePath = $themeDir . '/' . ($theme['assets']['signsDir'] ?? 'signs/') . $cleanName . '.' . $ext;
        $dataUri = ChartSvgBuilder::fileToDataUri($filePath);

        $unicodes = [
            'aries' => '♈',
            'taurus' => '♉',
            'gemini' => '♊',
            'cancer' => '♋',
            'leo' => '♌',
            'virgo' => '♍',
            'libra' => '♎',
            'scorpio' => '♏',
            'sagittarius' => '♐',
            'capricorn' => '♑',
            'aquarius' => '♒',
            'pisces' => '♓',
        ];

        return [
            'name' => AstrologyTranslator::translate($cleanName),
            'iconUri' => $dataUri ?: null,
            'unicode' => $unicodes[$cleanName] ?? '?',
        ];
    }
}
