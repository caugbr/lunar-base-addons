<?php

use Plugins\VisualSnippets\Models\VisualSnippet;

if (!function_exists('visual_snippet_render')) {
    /**
     * Mescla o HTML do molde com os valores passados nos atributos do shortcode.
     *
     * @param VisualSnippet $snippet O modelo do molde base.
     * @param array $attributes Atributos passados na tag [vs-instance].
     * @param string|null $innerContent Conteúdo opcional entre [vs-instance]...[/vs-instance].
     * @return string HTML final pronto para exibição pública.
     */
    function visual_snippet_render(VisualSnippet $snippet, array $attributes = [], ?string $innerContent = null): string
    {
        $html = $snippet->content;

        if (empty($html)) {
            return '';
        }

        // Se não houver atributos nem conteúdo interno, apenas limpa os data-var via regex rápida
        if (empty($attributes) && empty($innerContent)) {
            return preg_replace('/\sdata-var=[\'"][^\'"]*[\'"]/', '', $html);
        }

        // Remove 'slug' e 'id' da lista de dados a substituir (chaves reservadas do shortcode)
        $data = collect($attributes)->except(['slug', 'id'])->toArray();

        // Mapeia conteúdo interno se existir tag de fechamento [/vs-instance]
        if (!empty($innerContent)) {
            $data['_content'] = trim($innerContent);
        }

        // Manipulação precisa do DOM
        $dom = new \DOMDocument();

        // Macete para forçar UTF-8 nativo e não quebrar acentuação no PHP 8.3
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="utf-8" ?>' . "<div>{$html}</div>",
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Busca cirúrgica por elementos que possuem o atributo data-var
        $nodes = $xpath->query('//*[@data-var]');

        foreach ($nodes as $node) {
            $varName = $node->getAttribute('data-var');

            if (array_key_exists($varName, $data)) {
                $value = (string) $data[$varName];
                $tagName = strtolower($node->nodeName);

                if ($tagName === 'img') {
                    // Imagens: altera o atributo src
                    $node->setAttribute('src', $value);
                } elseif ($tagName === 'a') {
                    // Links: se for URL válida/relativa troca o href; senão troca o texto do botão/link
                    $scheme = parse_url($value, PHP_URL_SCHEME);
                    if (str_starts_with($value, '/') || in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
                        $node->setAttribute('href', $value);
                    } else {
                        $node->nodeValue = $value;
                    }
                } else {
                    // Textos, títulos, parágrafos e blockquotes
                    $node->nodeValue = $value;
                }
            }

            // Remove o atributo do markup final para não expor variáveis no front-end
            $node->removeAttribute('data-var');
        }

        // Extrai o HTML limpo de dentro da div temporária
        $container = $dom->getElementsByTagName('div')->item(0);
        $output = '';

        if ($container) {
            foreach ($container->childNodes as $child) {
                $output .= $dom->saveHTML($child);
            }
        }

        return $output ?: $html;
    }
}

if (!function_exists('visual_snippets_payload_map')) {
    /**
     * Retorna a lista de snippets ativos com seus schemas de variáveis para o editor.
     *
     * @return array
     */
    function visual_snippets_payload_map(): array
    {
        if (function_exists('dbAvailable') && !dbAvailable('visual_snippets')) {
            return [];
        }

        return VisualSnippet::active()
            ->select(['id', 'slug', 'title', 'description', 'payload', 'thumbnail_url'])
            ->orderBy('title', 'asc')
            ->get()
            ->toArray();
    }
}
