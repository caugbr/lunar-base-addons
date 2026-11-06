@extends('admin.layout')

@section('header_title', 'Modelos de E-mail')
@section('header_subtitle', 'Gerencie mensagens pré-configuradas para envio')

@section('content')
@if(!isset($tableExists) || !$tableExists)
    <div class="admin-card">
        <div class="admin-empty-list" style="padding: 40px; text-align: center;">
            <div style="margin-bottom: 12px;">
                <x-lucide-alert-triangle class="lucid-icon" style="color: var(--color-warning, #f59e0b); width: 36px; height: 36px;" />
            </div>
            <h3>Tabela não encontrada</h3>
            <p class="admin-text-muted">
                A tabela <code>outreach_templates</code> ainda não foi criada no banco de dados. Execute as migrações do plugin.
            </p>
        </div>
    </div>
@else
<div class="admin-card">
    <div class="admin-card-header">
        <h2><x-lucide-files class="lucid-icon" /> Lista de Modelos</h2>
        <a href="{{ route('admin.outreach.templates.create') }}" class="admin-btn admin-btn-primary">
            <x-lucide-plus class="lucid-icon" /> <span>Novo Modelo</span>
        </a>
    </div>

    {{-- Filtros no Padrão Lunar Base --}}
    <form method="GET" action="{{ route('admin.outreach.templates.index') }}" class="admin-filters">
        <div class="admin-filters-row">
            <div class="admin-filter-group" style="flex: 1;">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    class="admin-filter-input"
                    placeholder="Buscar por identificação ou assunto do e-mail..."
                >
            </div>
            <div class="admin-filter-actions">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <x-lucide-filter class="lucid-icon" /> Filtrar
                </button>
                <a href="{{ route('admin.outreach.templates.index') }}" class="admin-btn admin-btn-secondary">
                    <x-lucide-brush-cleaning class="lucid-icon" /> Limpar
                </a>
            </div>
        </div>
    </form>

    {{-- Tabela de Modelos --}}
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Identificação / Assunto</th>
                    <th>Envios Realizados</th>
                    <th>Criado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $tmpl)
                <tr>
                    <td>
                        <strong>
                            <a href="{{ route('admin.outreach.templates.edit', $tmpl->id) }}">{{ $tmpl->name }}</a>
                        </strong>
                        <br>
                        <small class="admin-text-muted">Assunto: {{ $tmpl->subject }}</small>
                    </td>
                    <td>
                        <span class="admin-badge admin-badge-info">
                            {{ $tmpl->logs_count }} {{ $tmpl->logs_count === 1 ? 'envio' : 'envios' }}
                        </span>
                    </td>
                    <td>{{ $tmpl->created_at ? $tmpl->created_at->format('d/m/Y H:i') : '-' }}</td>
                    <td class="admin-actions">
                        <div>
                            {{-- NOVO: Botão Visualizar E-mail --}}
                            <a href="{{ route('admin.outreach.templates.show', $tmpl->id) }}" class="admin-btn admin-btn-secondary" title="Pré-visualizar E-mail">
                                <x-lucide-eye class="lucid-icon" />
                            </a>

                            <a href="{{ route('admin.outreach.templates.edit', $tmpl->id) }}" class="admin-btn admin-btn-secondary" title="Editar">
                                <x-lucide-pencil class="lucid-icon" />
                            </a>

                            <form method="POST" action="{{ route('admin.outreach.templates.destroy', $tmpl->id) }}" data-confirm="Excluir este modelo de e-mail?" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn-danger" title="Excluir">
                                    <x-lucide-trash-2 class="lucid-icon" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4">
                        <div class="admin-empty-list">
                            <div>
                                <x-lucide-circle-off class="lucid-icon" />
                            </div>
                            <h3>Nenhum modelo encontrado</h3>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="admin-pagination">
        {{ $templates->appends(request()->query())->links() }}
    </div>
</div>
@endif
@endsection
