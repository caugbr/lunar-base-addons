<?php

namespace Plugins\Sitemap\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Page;
use App\Models\Taxonomy;

class PublicSitemapController extends Controller
{
    public function generate()
    {
        $urls = [];

        // Adiciona a Home
        $urls[] = [
            'loc' => url('/'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0'
        ];

        // Posts (Lendo corretamente via setting do banco)
        if (setting('general.sitemap_include_posts', true)) {
            $posts = Post::where('status', 'published')->latest()->get();
            foreach ($posts as $post) {
                $urls[] = [
                    'loc' => $post->url ?? url('/blog/' . $post->slug),
                    'lastmod' => $post->updated_at->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8'
                ];
            }
        }

        // Páginas
        if (setting('general.sitemap_include_pages', true)) {
            $pages = Page::where('status', 'published')->get();
            foreach ($pages as $page) {
                $urls[] = [
                    'loc' => $page->url ?? url('/page/' . $page->slug),
                    'lastmod' => $page->updated_at->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.6'
                ];
            }
        }

        // Taxonomias
        if (setting('general.sitemap_include_taxonomies', true)) {
            $taxonomies = Taxonomy::with('terms')->get();
            foreach ($taxonomies as $taxonomy) {
                foreach ($taxonomy->terms as $term) {
                    $urls[] = [
                        'loc' => url('/blog/' . $taxonomy->slug . '/' . $term->slug),
                        'lastmod' => $term->updated_at?->toAtomString() ?? now()->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.5'
                    ];
                }
            }
        }

        return response()->view('sitemap::public.xml', compact('urls'))
            ->header('Content-Type', 'text/xml');
    }
}
