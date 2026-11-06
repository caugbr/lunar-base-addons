@extends('admin.layout')

@section('header_title', 'Visualizar Modelo')
@section('header_subtitle', "Simulação de envio: {$template->name}")

@push('styles')
<style>
    .mail-simulator-card {
        background-color: #ffffff;
        border: 1px solid var(--color-border, #e2e8f0);
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .mail-simulator-header {
        background-color: var(--color-bg-subtle, #f8fafc);
        border-bottom: 1px solid var(--color-border, #e2e8f0);
        padding: 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
        font-size: 0.875rem;
    }

    .mail-header-row {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
    }

    .mail-header-label {
        font-weight: 600;
        color: var(--color-text-muted, #64748b);
        width: 70px;
        flex-shrink: 0;
    }

    .mail-header-value {
        color: var(--color-text, #1e293b);
        word-break: break-word;
    }

    .mail-subject-value {
        font-weight: 700;
        font-size: 1rem;
        color: var(--color-text, #0f172a);
    }

    .mail-simulator-body {
        padding: 2rem 1.5rem;
        color: #2d3748;
        line-height: 1.7;
        font-size: 0.95rem;
        background-color: #ffffff;
        min-height: 240px;
    }

    .simulator-notice {
        background-color: #eff6ff;
        border-left: 4px solid var(--color-primary, #0056b3);
        padding: 0.75rem 1rem;
        margin-bottom: 1.25rem;
        font-size: 0.8125rem;
        color: #1e40af;
        border-radius: 0 6px 6px 0;
    }

    .sample-data-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        font-size: 0.8125rem;
    }

    .sample-data-list li {
        display: flex;
        justify-content: space-between;
        border-bottom: 1px dashed var(--color-border, #e2e8f0);
        padding-bottom: 0.35rem;
    }

    .sample-key {
        color: var(--color-text-muted, #64748b);
    }

    .sample-val {
        font-weight: 500;
        color: var(--color-text, #1e293b);
    }
</style>
@endpush

@section('content')
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-mail class="lucid-icon" /> {{ $template->name }}</h2>
        <div class="top-buttons">
            <a href="{{ route('admin.outreach.templates.edit', $template->id) }}" class="admin-btn admin-btn-primary">
                <x-lucide-pencil class="lucid-icon" /> <span>Editar Modelo</span>
            </a>
            <a href="{{ route('admin.outreach.templates.index') }}" class="admin-btn admin-btn-secondary">
                <x-lucide-arrow-left class="lucid-icon" /> <span>Voltar</span>
            </a>
        </div>
    </div>

    <div class="edit-page-inner">
        {{-- ========================================= --}}
        {{-- COLUNA PRINCIPAL: SIMULADOR DE E-MAIL    --}}
        {{-- ========================================= --}}
        <div class="main-column">
            <div class="simulator-notice">
                <x-lucide-info class="lucid-icon" style="width: 14px; height: 14px; vertical-align: middle; margin-right: 4px;" />
                Visualização aproximada de como o e-mail chega ao leitor, com as tags <code>@{{...}}</code> preenchidas com dados de teste.
            </div>

            <div class="mail-simulator-card">
                <div class="mail-simulator-header">
                    <div class="mail-header-row">
                        <span class="mail-header-label">De:</span>
                        <span class="mail-header-value">{{ config('mail.from.name', 'Você') }} &lt;{{ config('mail.from.address', 'seu-email@dominio.com') }}&gt;</span>
                    </div>
                    <div class="mail-header-row">
                        <span class="mail-header-label">Para:</span>
                        <span class="mail-header-value">{{ $sampleClient->name }} &lt;{{ $sampleClient->email }}&gt;</span>
                    </div>
                    <div class="mail-header-row">
                        <span class="mail-header-label">Assunto:</span>
                        <span class="mail-header-value mail-subject-value">{{ $parsed['subject'] }}</span>
                    </div>
                </div>

                {{-- Corpo Renderizado --}}
                <div class="mail-simulator-body">
                    {!! $parsed['body'] !!}
                </div>
            </div>
        </div>

        {{-- ========================================= --}}
        {{-- COLUNA LATERAL (ASIDE)                     --}}
        {{-- ========================================= --}}
        <div class="aside-column">
            {{-- Box 1: Estatísticas do Modelo --}}
            <div class="edit-box">
                <header>Métricas do Modelo</header>
                <article>
                    <div style="font-size: 0.875rem; display: flex; flex-direction: column; gap: 0.5rem;">
                        <div>
                            <strong>Envios Realizados:</strong>
                            <span class="admin-badge admin-badge-info">{{ $template->logs()->count() }}</span>
                        </div>
                        <div>
                            <strong>Criado em:</strong>
                            {{ $template->created_at ? $template->created_at->format('d/m/Y H:i') : '-' }}
                        </div>
                        <div>
                            <strong>Última atualização:</strong>
                            {{ $template->updated_at ? $template->updated_at->format('d/m/Y H:i') : '-' }}
                        </div>
                    </div>
                </article>
            </div>

            {{-- Box 2: Dados Usados no Teste --}}
            <div class="edit-box">
                <header>Amostra Utilizada</header>
                <article>
                    <ul class="sample-data-list">
                        <li>
                            <span class="sample-key">@{{primeiro_nome}}:</span>
                            <span class="sample-val">{{ $sampleClient->first_name }}</span>
                        </li>
                        <li>
                            <span class="sample-key">@{{nome}}:</span>
                            <span class="sample-val">{{ $sampleClient->name }}</span>
                        </li>
                        <li>
                            <span class="sample-key">@{{empresa}}:</span>
                            <span class="sample-val">{{ $sampleClient->company }}</span>
                        </li>
                        <li>
                            <span class="sample-key">@{{cargo}}:</span>
                            <span class="sample-val">{{ $sampleClient->role }}</span>
                        </li>
                        <li>
                            <span class="sample-key">@{{site}}:</span>
                            <span class="sample-val">{{ $sampleClient->website }}</span>
                        </li>
                    </ul>
                </article>
            </div>
        </div>
    </div>
</div>
@endsection
