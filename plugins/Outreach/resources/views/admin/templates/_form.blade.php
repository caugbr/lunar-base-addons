@php
    $isEdit = $template->exists;
@endphp

@push('styles')
<style>
    .variables-guide-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 0.625rem;
    }

    .variables-guide-list li {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.8125rem;
    }

    .variables-guide-list code {
        font-family: monospace;
        cursor: pointer;
        padding: 2px 6px;
        background-color: var(--color-bg-subtle, #f1f5f9);
        border: 1px solid var(--color-border, #cbd5e1);
        border-radius: 4px;
        color: var(--color-primary, #0056b3);
        transition: background-color 0.15s ease;
    }

    .variables-guide-list code:hover {
        background-color: #e2e8f0;
    }

    .variables-guide-help {
        font-size: 0.8125rem;
        color: var(--color-text-muted, #64748b);
        margin: 0 0 0.875rem;
        line-height: 1.4;
    }
</style>
@endpush

<x-form
    :action="$action"
    :method="$isEdit ? 'PUT' : 'POST'"
    :id="($isEdit ? 'edit' : 'create') . '_template_form'"
    :warn-changes="$isEdit"
>
    <div class="edit-page-inner">
        {{-- ========================================= --}}
        {{-- COLUNA PRINCIPAL                          --}}
        {{-- ========================================= --}}
        <div class="main-column">
            {{-- Identificação interna --}}
            <div class="form-row">
                <x-form.text
                    name="name"
                    label="Identificação do Modelo"
                    :value="old('name', $template->name)"
                    placeholder="Ex: Apresentação Inicial - Varejo"
                    help="Nome visível apenas para você no painel para organizar os modelos"
                    required
                />
            </div>

            {{-- Assunto do e-mail --}}
            <div class="form-row">
                <x-form.text
                    name="subject"
                    label="Assunto do E-mail"
                    :value="old('subject', $template->subject)"
                    placeholder="Ex: Olá, @{{primeiro_nome}}! Uma proposta para a @{{empresa}}"
                    help="Você pode usar as tags dinâmicas também no assunto"
                    required
                />
            </div>

            {{-- Editor Tiptap para o corpo da mensagem --}}
            <div class="form-group">
                <label class="form-label">Corpo da Mensagem <span class="form-required">*</span></label>
                <x-editor
                    name="body"
                    json-name="body_json"
                    exclude-tools="zen_mode"
                    :sticky-toolbar="false"
                    :value="old('body', $template->body)"
                    :json="old('body_json', $template->body_json ?: null)"
                />
                @error('body') <small class="form-error">{{ $message }}</small> @enderror
            </div>
        </div>

        {{-- ========================================= --}}
        {{-- COLUNA LATERAL (ASIDE)                     --}}
        {{-- ========================================= --}}
        <div class="aside-column">
            {{-- Box 1: Ações de Salvar --}}
            <div class="edit-box">
                <header>Ações</header>
                <article>
                    <p class="variables-guide-help">
                        {{ $isEdit ? 'As alterações serão aplicadas apenas aos próximos envios em lote.' : 'Após salvar, o modelo estará disponível imediatamente na listagem de contatos.' }}
                    </p>

                    <div class="buttons">
                        <x-form.button type="submit" variant="primary">
                            <x-lucide-save class="lucid-icon" /> {{ $isEdit ? 'Atualizar' : 'Salvar Modelo' }}
                        </x-form.button>
                        {{-- <a href="{{ route('admin.outreach.templates.index') }}" class="admin-btn admin-btn-secondary">
                            Voltar
                        </a> --}}
                    </div>
                </article>
            </div>

            {{-- Box 2: Tags e Variáveis Dinâmicas --}}
            <div class="edit-box">
                <header>Variáveis do e-mail</header>
                <article>
                    <p class="variables-guide-help">
                        Clique em uma tag para copiá-la e use-a no assunto ou no corpo do texto:
                    </p>

                    <ul class="variables-guide-list">
                        <li>
                            <code onclick="navigator.clipboard.writeText(this.innerText)">@{{primeiro_nome}}</code>
                            <span>Primeiro nome</span>
                        </li>
                        <li>
                            <code onclick="navigator.clipboard.writeText(this.innerText)">@{{nome}}</code>
                            <span>Nome completo</span>
                        </li>
                        <li>
                            <code onclick="navigator.clipboard.writeText(this.innerText)">@{{empresa}}</code>
                            <span>Nome da empresa</span>
                        </li>
                        <li>
                            <code onclick="navigator.clipboard.writeText(this.innerText)">@{{cargo}}</code>
                            <span>Cargo</span>
                        </li>
                        <li>
                            <code onclick="navigator.clipboard.writeText(this.innerText)">@{{email}}</code>
                            <span>E-mail</span>
                        </li>
                        <li>
                            <code onclick="navigator.clipboard.writeText(this.innerText)">@{{telefone}}</code>
                            <span>Telefone</span>
                        </li>
                        <li>
                            <code onclick="navigator.clipboard.writeText(this.innerText)">@{{site}}</code>
                            <span>Website</span>
                        </li>
                    </ul>
                </article>
            </div>
        </div>
    </div>

    {{-- Botões de Rodapé --}}
    <div class="buttons bottom-buttons">
        <x-form.button type="submit" variant="primary">
            <x-lucide-save class="lucid-icon" /> {{ $isEdit ? 'Atualizar' : 'Salvar Modelo' }}
        </x-form.button>
        <a href="{{ route('admin.outreach.templates.index') }}" class="admin-btn admin-btn-secondary">
            Voltar
        </a>
    </div>
</x-form>
