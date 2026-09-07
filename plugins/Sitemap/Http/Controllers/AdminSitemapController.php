<?php

namespace Plugins\Sitemap\Http\Controllers;

use App\Http\Controllers\Controller;

class AdminSitemapController extends Controller
{
    public function index()
    {
        $sitemapUrl = route('sitemap.xml');
        return view('sitemap::admin.index', compact('sitemapUrl'));
    }
}
