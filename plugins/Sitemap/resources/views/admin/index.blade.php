@extends('admin.layout')

@section('header_title', 'Sitemap XML')
@section('header_subtitle', 'Gerenciamento e link de indexação para motores de busca')

@section('content')
<x-admin-alert />

<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-globe class="lucid-icon" /> Status do Sitemap XML</h2>
    </div>

    <div style="padding: 20px;">
        <p>O sitemap do seu site está sendo gerado dinamicamente em tempo real com base nas suas publicações e nas configurações globais.</p>

        <div class="form-group">
            <input type="text" value="{{ $sitemapUrl }}" class="admin-input" readonly style="max-width: 500px;" id="sitemapInput">
            <button type="button" class="admin-btn admin-btn-secondary" onclick="navigator.clipboard.writeText(document.getElementById('sitemapInput').value); alert('Link copiado para a área de transferência!');" title="Copiar Link">
                <x-lucide-copy class="lucid-icon" />
            </button>
        </div>

        <div class="buttons">
            <a href="{{ $sitemapUrl }}" target="_blank" class="admin-btn admin-btn-primary">
                <x-lucide-external-link class="lucid-icon" /> Visualizar XML
            </a>
        </div>

        <div class="admin-help-text" style="margin-top: 15px;">
            <strong>Dica de SEO:</strong> Envie o link acima (<code>{{ $sitemapUrl }}</code>) para o <strong>Google Search Console</strong> e para o <strong>Bing Webmaster Tools</strong> para acelerar a indexação das páginas do seu site.
        </div>
    </div>
</div>
@endsection
