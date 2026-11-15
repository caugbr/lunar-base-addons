<?php

namespace Plugins\Astrology\Services;

class AstrologyTranslator
{
    protected const TERMS = [
        // Signos
        'aries'       => 'Áries',
        'taurus'      => 'Touro',
        'gemini'      => 'Gêmeos',
        'cancer'      => 'Câncer',
        'leo'         => 'Leão',
        'virgo'       => 'Virgem',
        'libra'       => 'Libra',
        'scorpio'     => 'Escorpião',
        'sagittarius' => 'Sagitário',
        'capricorn'   => 'Capricórnio',
        'aquarius'    => 'Aquário',
        'pisces'      => 'Peixes',

        // Planetas e pontos
        'sun'         => 'Sol',
        'moon'        => 'Lua',
        'mercury'     => 'Mercúrio',
        'venus'       => 'Vênus',
        'mars'        => 'Marte',
        'jupiter'     => 'Júpiter',
        'saturn'      => 'Saturno',
        'uranus'      => 'Urano',
        'neptune'     => 'Netuno',
        'pluto'       => 'Plutão',
        'north node'  => 'Nodo Norte',
        'south node'  => 'Nodo Sul',
        'true node'   => 'Nodo Verdadeiro',
        'chiron'      => 'Quíron',
        'lilith'      => 'Lilith',

        // Angulos
        'ascendant'   => 'Ascendente',
        'mc'          => 'Meio do Céu',
        'midheaven'   => 'Meio do Céu',
        'descendant'  => 'Descendente',
        'imum coeli'  => 'Fundo do Céu',
        'ic'          => 'Fundo do Céu',

        // Aspectos e estados
        'conjunction'    => 'Conjunção',
        'opposition'     => 'Oposição',
        'trine'          => 'Trígono',
        'square'         => 'Quadratura',
        'sextile'        => 'Sextil',
        'quincunx'       => 'Quincúncio',
        'semisquare'     => 'Semiquadratura',
        'sesquiquadrate' => 'Sesquiquadratura',
        'semisextile'    => 'Semisextil',
        'applying'       => 'Aplicativo',
        'separating'     => 'Separativo',

        // Sistemas de casas
        'placidus'      => 'Placidus',
        'koch'          => 'Koch',
        'equal'         => 'Casas Iguais',
        'whole_sign'    => 'Signos Inteiros',
        'campanus'      => 'Campanus',
        'regiomontanus' => 'Regiomontanus',
        'porphyry'      => 'Porfírio',
        'topocentric'   => 'Topocêntrico',
    ];

    public static function translate(?string $term, ?string $fallback = null): string
    {
        if ($term === null || $term === '') {
            return $fallback ?? '—';
        }

        $key = strtolower(trim($term));

        return self::TERMS[$key] ?? ($fallback ?? $term);
    }

    public static function translateFormatted(?string $formatted): string
    {
        if ($formatted === null || $formatted === '') {
            return '';
        }

        return (string) preg_replace_callback(
            '/\b(Aries|Taurus|Gemini|Cancer|Leo|Virgo|Libra|Scorpio|Sagittarius|Capricorn|Aquarius|Pisces)\b/i',
            fn (array $matches) => self::translate($matches[1]),
            $formatted
        );
    }
}
