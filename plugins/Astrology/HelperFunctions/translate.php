<?php

if (!function_exists('astro_pt')) {
    function astro_pt(?string $term, ?string $fallback = null): string
    {
        return \Plugins\Astrology\Services\AstrologyTranslator::translate($term, $fallback);
    }
}

if (!function_exists('astro_pos_pt')) {
    function astro_pos_pt(?string $formatted): string
    {
        return \Plugins\Astrology\Services\AstrologyTranslator::translateFormatted($formatted);
    }
}
