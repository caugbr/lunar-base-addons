@php
    $isEdit = isset($snippet) && $snippet->exists;

    // Configurações do editor
    $editorSettings = settingsGroup('editor');
    $rawPalette = $editorSettings['color_palette'] ?? '#0f172a, #64748b, #ef4444, #f97316, #eab308, #22c55e, #3b82f6, #a855f7, #ec4899';
    $colorPalette = is_array($rawPalette) ? $rawPalette : array_values(array_filter(array_map('trim', explode(',', $rawPalette))));

    $rawTools = $editorSettings['toolbar_tools'] ?? [];
    $toolbarTools = is_array($rawTools) ? $rawTools : (is_string($rawTools) ? array_values(array_filter(array_map('trim', explode(',', $rawTools)))) : []);

    $editorConfig = [
        'tools'              => $toolbarTools,
        'colors'             => $colorPalette,
        'allow_custom_color' => (bool) ($editorSettings['allow_custom_color'] ?? true),
        'group_inline_tools' => (bool) ($editorSettings['group_inline_tools'] ?? true),
        'compact_dropdowns'  => (bool) ($editorSettings['compact_dropdowns'] ?? true),
        'sticky_toolbar'     => (bool) ($editorSettings['sticky_toolbar'] ?? true),
    ];
    $editorConfigJson = json_encode($editorConfig);

    $initialJson = old('content_json', $isEdit ? $snippet->content_json : null);
    $initialHtml = old('content', $isEdit ? $snippet->content : '');
    $initialThumb = old('thumbnail_url', $isEdit ? $snippet->thumbnail_url : '');
@endphp

<form method="POST" action="{{ $action }}" id="{{ $isEdit ? 'edit' : 'create' }}_form">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="edit-page-inner">
        {{-- ======================================================== --}}
        {{-- COLUNA PRINCIPAL: TÍTULO E EDITOR TIPTAP                 --}}
        {{-- ======================================================== --}}
        <div class="main-column">
            {{-- Título --}}
            <div class="admin-form-row">
                <div class="form-group">
                    <input type="text" name="title" id="title" class="form-input"
                           value="{{ old('title', $snippet->title ?? '') }}"
                           placeholder="Título do Snippet Visual" required>
                    @error('title') <small class="error">{{ $message }}</small> @enderror
                </div>
            </div>

            {{-- Área do Editor Tiptap --}}
            <div class="form-group">
                <div class="lunar-editor-wrapper">
                    <div
                        id="lunar-tiptap-app"
                        data-initial-json="{{ is_array($initialJson) ? json_encode($initialJson) : ($initialJson ?? '') }}"
                        data-initial-html="{{ $initialHtml }}"
                        data-editor-config="{{ $editorConfigJson }}"
                    ></div>

                    {{-- Inputs ocultos de persistência --}}
                    <input type="hidden" name="content_json" id="lunar_content_json"
                           value="{{ is_array($initialJson) ? json_encode($initialJson) : $initialJson }}">
                    <input type="hidden" name="content" id="lunar_content_html"
                           value="{{ $initialHtml }}">
                    <input type="hidden" name="payload" id="snippet_payload"
                           value="{{ json_encode(old('payload', $snippet->payload ?? [])) }}">
                </div>
                @error('content') <small class="error">{{ $message }}</small> @enderror
            </div>

            {{-- Descrição do Molde --}}
            <div class="edit-box">
                <header>Descrição e Instruções</header>
                <article>
                    <div class="form-group excerpt">
                        <textarea name="description" id="description" rows="3"
                                  placeholder="Explique onde ou como utilizar este bloco (opcional)...">{{ old('description', $snippet->description ?? '') }}</textarea>
                        <small>Ajuda os redatores a entenderem o propósito deste layout.</small>
                    </div>
                </article>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- COLUNA LATERAL: DETALHES, SLUG E THUMBNAIL               --}}
        {{-- ======================================================== --}}
        <div class="aside-column">
            {{-- Box 1: Publicação e Ações --}}
            <div class="edit-box">
                <header>Detalhes do Snippet</header>
                <article>
                    <div class="form-group">
                        <label for="slug">Slug (Único) *</label>
                        <input type="text" name="slug" id="slug" class="form-input"
                               value="{{ old('slug', $snippet->slug ?? '') }}"
                               placeholder="ex: card-depoimento" required>
                        <small>Identificador nos shortcodes: <code>[vs-template slug="..."]</code></small>
                        @error('slug') <small class="error">{{ $message }}</small> @enderror
                    </div>

                    <div class="form-group">
                        <label for="is_active">Disponível no Editor?</label>
                        <x-switch name="is_active" id="is_active"
                                  checked="{{ old('is_active', $snippet->is_active ?? true) }}"
                                  active="Ativo" inactive="Inativo" />
                    </div>

                    <div class="buttons">
                        <button type="submit" class="admin-btn admin-btn-primary" style="width: 100%;">
                            <x-lucide-save class="lucid-icon" /> {{ $isEdit ? 'Atualizar Snippet' : 'Salvar Snippet' }}
                        </button>
                    </div>
                </article>
            </div>

            {{-- Box de Miniatura (100% no padrão do form de páginas) --}}
            <div class="edit-box" x-data="snippetThumbnail('{{ $initialThumb }}')">
                <header>Miniatura do Snippet</header>
                <article>
                    <div class="form-group thumbnail">
                        <div class="thumbnail-selector">
                            <div class="thumbnail-preview"
                                :class="{ 'has-image': thumbnailUrl }"
                                @click="!thumbnailUrl && captureFromEditor()">

                                <template x-if="thumbnailUrl">
                                    <img :src="thumbnailUrl" alt="Preview" class="preview-image" @click="captureFromEditor()">
                                </template>

                                <template x-if="!thumbnailUrl">
                                    <div class="preview-placeholder">
                                        <x-lucide-image class="lucid-icon" />
                                        <span>Clique para fotografar</span>
                                    </div>
                                </template>

                                <button type="button"
                                        x-show="thumbnailUrl"
                                        @click.stop="clearMedia()"
                                        class="preview-remove"
                                        title="Remover imagem">
                                    <x-lucide-x class="lucid-icon" />
                                </button>
                            </div>

                            <div class="thumbnail-actions" x-show="!thumbnailUrl">
                                <button type="button"
                                        @click="captureFromEditor()"
                                        class="admin-btn admin-btn-secondary">
                                    <x-lucide-camera class="lucid-icon" /> Fotografar
                                </button>
                            </div>

                            <input type="hidden" name="thumbnail_url" x-model="thumbnailUrl">
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </div>

    <div class="buttons bottom-buttons" style="margin-top: 1.5rem;">
        <button type="submit" class="admin-btn admin-btn-primary">
            <x-lucide-save class="lucid-icon" /> {{ $isEdit ? 'Atualizar' : 'Salvar' }}
        </button>
        <a href="{{ route('admin.visual-snippets.index') }}" class="admin-btn admin-btn-secondary">
            <x-lucide-arrow-left class="lucid-icon" /> Voltar
        </a>
    </div>
</form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('create_form') || document.getElementById('edit_form');

        if (form) {
            form.addEventListener('submit', (e) => {
                const editorEl = document.querySelector('.lunar-editor-wrapper .tiptap');
                const htmlInput = document.getElementById('lunar_content_html');
                const jsonInput = document.getElementById('lunar_content_json');

                // Se o Tiptap deixou o input em branco, preenche diretamente do DOM!
                if (editorEl && htmlInput) {
                    const htmlContent = editorEl.innerHTML.trim();

                    // Se tiver qualquer conteúdo real (mesmo que seja só tags de blocos)
                    if (htmlContent && htmlContent !== '<p></p>') {
                        htmlInput.value = htmlContent;
                    }
                }
            });
        }
    });
</script>
@endpush

@if($isEdit)
    <x-lost-changes-warn selector="#edit_form" />
@endif

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/tiptap-editor.css') }}">
    @if(class_exists('\App\Services\EditorManager'))
        @foreach(\App\Services\EditorManager::getStyles() as $style)
            <link rel="stylesheet" href="{{ $style }}">
        @endforeach
    @endif
    <style>
        /* Desativa as bordas e etiquetas verdes enquanto tira a foto! */
        .lunar-capturing-thumb .tiptap [data-var] {
            outline: none !important;
        }
        .lunar-capturing-thumb .tiptap [data-var]::before {
            display: none !important;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ asset('plugins/visual-snippets/js/html-to-image.min.js') }}"></script>

    <script>
        @if(class_exists('\App\Services\EditorManager'))
            window.LunarKnownBlocks = @json(\App\Services\EditorManager::editorBlocksMap());
        @else
            window.LunarKnownBlocks = {};
        @endif

        // Componente Alpine para a Thumbnail (espelhando o thumbnailManager nativo)
        function snippetThumbnail(initialUrl) {
            return {
                thumbnailUrl: initialUrl || '',
                clearMedia() {
                    this.thumbnailUrl = '';
                    window.dispatchEvent(new CustomEvent('editor:dirty'));
                },
                async captureFromEditor() {
                    const targetEl = document.querySelector('.lunar-editor-wrapper .editor-content-box');
                    if (!targetEl || typeof htmlToImage === 'undefined') {
                        if (window.Dialog) Dialog.alert('Não foi possível localizar o editor.');
                        return;
                    }

                    // 1. Esconde as marcações verdes e a barra da mira
                    document.body.classList.add('lunar-capturing-thumb');

                    try {
                        // 2. Fotografa em JPEG de qualidade alta
                        // const dataUrl = await htmlToImage.toJpeg(targetEl, {
                        //     quality: 0.85,
                        //     backgroundColor: '#ffffff'
                        // });
                        const dataUrl = await htmlToImage.toJpeg(targetEl, {
                            quality: 0.85,
                            backgroundColor: '#ffffff',
                            // 1. Não tenta fazer fetch de CSS externos/relativos que geram 404
                            skipAutoScale: true,
                            // 2. Não tenta baixar webfonts remotas (usa as que já estão no DOM)
                            fontEmbedCSS: '',
                            // 3. Ignora tags <link> com caminhos relativos
                            filter: (node) => {
                                // Se por algum motivo encontrar botões da admin bar interna, ignora
                                if (node.classList && (
                                    node.classList.contains('card-admin-bar') ||
                                    node.classList.contains('columns-admin-bar') ||
                                    node.classList.contains('cta-controls')
                                )) {
                                    return false;
                                }
                                return true;
                            }
                        });

                        // 3. Aplica na thumbnail reativa
                        this.thumbnailUrl = dataUrl;
                        window.dispatchEvent(new CustomEvent('editor:dirty'));
                    } catch (error) {
                        console.error('Erro ao capturar miniatura:', error);
                        if (window.Dialog) Dialog.alert('Falha ao fotografar o editor.');
                    } finally {
                        // 4. Devolve as marcas verdes para a tela
                        document.body.classList.remove('lunar-capturing-thumb');
                    }
                }
            };
        }
    </script>
    <script>
        window.LUNAR_INIT_EDITOR_DATA = window.LUNAR_INIT_EDITOR_DATA || {};
        window.LUNAR_INIT_EDITOR_DATA.attributes = window.LUNAR_INIT_EDITOR_DATA.attributes || [];
        if (!window.LUNAR_INIT_EDITOR_DATA.attributes.some(a => a.name === "data-var")) {
            window.LUNAR_INIT_EDITOR_DATA.attributes.push({
                name: "data-var",
                types: "*"
            });
        }
    </script>
    <script type="module" src="{{ asset('js/tiptap-editor.js') }}"></script>
    @if(class_exists('\App\Services\EditorManager'))
        @foreach(\App\Services\EditorManager::getScripts() as $script)
            <script type="module" src="{{ $script }}"></script>
        @endforeach
    @endif
    <script src="{{ asset('js/editor-common.js') }}"></script>
@endpush
