@extends('admin.layout')

@section('header_title', 'Snippets Visuais')
@section('header_subtitle', 'Pré-visualização: ' . $snippet->title)

@section('content')
<link rel="stylesheet" href="{{ asset('css/public/vars.css') }}">
<link rel="stylesheet" href="{{ asset('css/tiptap-editor.css') }}">

<div class="admin-card">
    <div class="admin-card-header snippet-show-header">
        <h2>
            <x-lucide-eye class="lucid-icon" />
            <span>Pré-visualização do Snippet</span>
        </h2>
        <div class="snippet-show-actions">
            {{-- Status usando as classes admin-badge nativas --}}
            @if(!$snippet->is_active)
                <span class="admin-badge admin-badge-inactive">Inativo</span>
            @else
                <span class="admin-badge admin-badge-active">Ativo</span>
            @endif

            <a href="{{ route('admin.visual-snippets.edit', $snippet->id) }}" class="admin-btn admin-btn-primary">
                <x-lucide-pencil class="lucid-icon" /> <span>Editar</span>
            </a>

            <a href="{{ route('admin.visual-snippets.index') }}" class="admin-btn admin-btn-secondary">
                <x-lucide-arrow-left class="lucid-icon" /> <span>Voltar</span>
            </a>
        </div>
    </div>

    {{-- Caixa com as Instruções de Shortcode --}}
    <div class="snippet-shortcodes-bar">
        <div class="snippet-shortcode-item">
            <span class="shortcode-label">Shortcode Global</span>
            <div class="shortcode-code-wrap">
                <code class="shortcode-box">[vs-template slug="{{ $snippet->slug }}"]</code>
            </div>
        </div>

        <div class="snippet-shortcode-item">
            <span class="shortcode-label">Shortcode com Substituição</span>
            <div class="shortcode-code-wrap">
                <code class="shortcode-box">[vs-instance slug="{{ $snippet->slug }}" ...atributos]</code>
            </div>
        </div>
    </div>

    {{-- Área de Renderização com Alternador de Tema --}}
    <div class="snippet-preview-wrapper" data-theme="light">
        <div class="snippet-theme-switcher">
            <x-switch name="theme-switch" id="theme-switch" active="Dark" inactive="Light" />
        </div>

        <div class="snippet-rendered-content">
            {!! $snippet->content !!}
        </div>
    </div>
</div>

<style>
    /* Cabeçalho da visualização */
    .snippet-show-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .snippet-show-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Barra de Shortcodes */
    .snippet-shortcodes-bar {
        display: flex;
        gap: 2rem;
        flex-wrap: wrap;
        background: var(--color-bg-light, #f8fafc);
        border: 1px solid var(--color-border, #e2e8f0);
        border-radius: 8px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
    }

    .snippet-shortcode-item {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .shortcode-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: var(--color-text-muted, #64748b);
        font-weight: 700;
        letter-spacing: 0.5px;
    }

    .shortcode-box {
        background: var(--color-card-bg, #ffffff);
        border: 1px solid var(--color-border, #cbd5e1);
        padding: 3px 8px;
        border-radius: 4px;
        font-weight: 600;
        color: var(--color-text, #0f172a);
    }

    /* Canvas de Preview */
    .snippet-preview-wrapper {
        background-color: #f8fafc;
        padding: 3rem 2rem;
        border: 1px solid var(--color-border, #e2e8f0);
        border-radius: 0.5rem;
        width: 100%;
        max-width: 900px;
        margin: 0 auto;
        position: relative;
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    .snippet-preview-wrapper[data-theme="dark"] {
        background-color: #0f172a;
        border-color: #1e293b;
        color: #f8fafc;
    }

    .snippet-theme-switcher {
        position: absolute;
        top: 12px;
        right: 16px;
    }

    .snippet-rendered-content {
        width: 100%;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const switcher = document.querySelector('#theme-switch');
        if (switcher) {
            const wrapper = document.querySelector('.snippet-preview-wrapper');
            switcher.addEventListener('input', function() {
                wrapper.dataset.theme = this.checked ? 'dark' : 'light';
            });
        }
    });
</script>
@endsection
