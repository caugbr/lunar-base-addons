<?php

if (!function_exists('getTermColor')) {
    /**
     * Obtém a cor hexadecimal associada a um termo de taxonomia.
     *
     * @param int|object $term ID do termo ou o próprio Model/objeto Term
     * @param string $default Cor hexadecimal de fallback (padrão: #000000)
     * @return string Cor em formato hexadecimal (ex: #ff0000)
     */
    function getTermColor($term, string $default = '#000000'): string
    {
        // Se passarem o objeto $term direto, extrai o ID
        $termId = is_object($term) ? ($term->id ?? null) : (int) $term;

        if (!$termId) {
            return $default;
        }

        $termColors = getOption('term_colors', []);

        return $termColors[$termId] ?? $default;
    }
}
