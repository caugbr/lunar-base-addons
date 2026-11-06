@php
    $isEdit = $client->exists;
    $statusOptions = [
        'novo'        => 'Novo',
        'contatado'   => 'Contatado',
        'em_conversa' => 'Em Conversa',
        'fechado'     => 'Fechado',
        'descartado'  => 'Descartado',
    ];
@endphp

<x-form
    :action="$action"
    :method="$isEdit ? 'PUT' : 'POST'"
    :id="($isEdit ? 'edit' : 'create') . '_client_form'"
    :warn-changes="$isEdit"
>
    <div class="edit-page-inner">
        {{-- ========================================= --}}
        {{-- COLUNA PRINCIPAL                          --}}
        {{-- ========================================= --}}
        <div class="main-column">
            {{-- Nome e E-mail --}}
            <div class="form-row">
                <x-form.text
                    name="name"
                    label="Nome do Contato"
                    :value="old('name', $client->name)"
                    placeholder="Ex: Carlos Eduardo Silva"
                    required
                />

                <x-form.text
                    type="email"
                    name="email"
                    label="E-mail"
                    :value="old('email', $client->email)"
                    placeholder="carlos@empresa.com"
                    required
                />
            </div>

            {{-- Empresa e Cargo --}}
            <div class="form-row">
                <x-form.text
                    name="company"
                    label="Empresa"
                    :value="old('company', $client->company)"
                    placeholder="Ex: Acme Comércio Ltda"
                />

                <x-form.text
                    name="role"
                    label="Cargo"
                    :value="old('role', $client->role)"
                    placeholder="Ex: Diretor de Operações"
                />
            </div>

            {{-- Telefone e Website --}}
            <div class="form-row">
                <x-form.text
                    name="phone"
                    label="Telefone / WhatsApp"
                    :value="old('phone', $client->phone)"
                    placeholder="(11) 98765-4321"
                />

                <x-form.text
                    type="url"
                    name="website"
                    label="Site da Empresa"
                    :value="old('website', $client->website)"
                    placeholder="https://empresa.com"
                />
            </div>

            {{-- Dentro da .main-column de clients/_form.blade.php, logo após o row de telefone/site: --}}
            @if(!$isEdit)
                <div class="form-row">
                    <x-form.textarea
                        name="initial_note"
                        label="Anotação Inicial (opcional)"
                        placeholder="Ex: Como conheceu o lead, contexto da empresa, observações para o primeiro contato..."
                        rows="3"
                        :value="old('initial_note')"
                        help="Esta nota será registrada na linha do tempo do contato"
                    />
                </div>
            @endif
        </div>

        {{-- ========================================= --}}
        {{-- COLUNA LATERAL (ASIDE)                     --}}
        {{-- ========================================= --}}
        <div class="aside-column">
            <div class="edit-box">
                <header>Status do Lead</header>
                <article>
                    <x-form.select
                        name="status"
                        label="Status Atual"
                        :options="$statusOptions"
                        :value="old('status', $client->status ?? 'novo')"
                        :placeholder="false"
                        required
                    />

                    <div class="buttons">
                        <x-form.button type="submit" variant="primary">
                            <x-lucide-save class="lucid-icon" /> {{ $isEdit ? 'Atualizar Contato' : 'Salvar Contato' }}
                        </x-form.button>
                        {{-- @if($isEdit)
                            <a href="{{ route('admin.outreach.clients.show', $client->id) }}" class="admin-btn admin-btn-secondary">
                                Ver Perfil
                            </a>
                        @else
                            <a href="{{ route('admin.outreach.clients.index') }}" class="admin-btn admin-btn-secondary">
                                Voltar
                            </a>
                        @endif --}}
                    </div>
                </article>
            </div>
        </div>
    </div>

    {{-- Botões Fixos no Rodapé --}}
    <div class="buttons bottom-buttons">
        <x-form.button type="submit" variant="primary">
            <x-lucide-save class="lucid-icon" /> {{ $isEdit ? 'Atualizar Contato' : 'Salvar Contato' }}
        </x-form.button>
        <a href="{{ route('admin.outreach.clients.index') }}" class="admin-btn admin-btn-secondary">
            Voltar
        </a>
    </div>
</x-form>
