<x-modal id="visualSnippetPickerModal" title="Inserir Snippet Visual" size="full">
    <div x-data="snippetPickerInserter()" x-init="init()" style="min-height: 60vh;">

        {{-- ======================================================== --}}
        {{-- PASSO 1: A GALERIA DE MOLDES (GRID DE CARDS)            --}}
        {{-- ======================================================== --}}
        <div x-show="step === 'gallery'">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <input type="text" x-model="searchQuery" class="form-input" placeholder="Filtrar snippets por título..." style="max-width: 350px;">
                <span class="admin-text-muted" style="font-size: 0.85rem;" x-text="filteredSnippets.length + ' moldes disponíveis'"></span>
            </div>

            <div class="snippet-picker-grid">
                <template x-for="snippet in filteredSnippets" :key="snippet.id">
                    <div class="snippet-picker-card" @click="selectSnippet(snippet)">
                        <div class="snippet-picker-thumb">
                            <template x-if="snippet.thumbnail_url">
                                <img :src="snippet.thumbnail_url" :alt="snippet.title">
                            </template>
                            <template x-if="!snippet.thumbnail_url">
                                <div class="snippet-picker-placeholder">
                                    <x-lucide-image class="lucid-icon" style="width: 32px; height: 32px; color: #94a3b8;" />
                                    <span>Sem preview</span>
                                </div>
                            </template>
                        </div>
                        <div class="snippet-picker-body">
                            <h4 class="snippet-picker-title" x-text="snippet.title"></h4>
                            <p class="snippet-picker-desc" x-text="snippet.description || 'Nenhuma descrição informada.'"></p>
                            <code class="snippet-picker-slug" x-text="'[vs-template slug=&quot;' + snippet.slug + '&quot;]'"></code>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- ======================================================== --}}
        {{-- PASSO 2: O FORMULÁRIO DINÂMICO DE VARIÁVEIS              --}}
        {{-- ======================================================== --}}
        <div x-show="step === 'form'" style="max-width: 650px; margin: 0 auto;">
            {{-- <button type="button" @click="step = 'gallery'" class="admin-btn admin-btn-secondary" style="margin-bottom: 1.25rem; font-size: 0.8rem;"> --}}
            <button type="button" @click="goToGallery()" class="admin-btn admin-btn-secondary" style="margin-bottom: 1.25rem; font-size: 0.8rem;">
                <x-lucide-arrow-left class="lucid-icon" /> Voltar para a galeria
            </button>

            <h3 x-text="activeSnippet?.title" style="margin-top: 0; font-size: 1.25rem; font-weight: 600; color: #1e293b;"></h3>
            <p x-text="activeSnippet?.description" style="color: #64748b; font-size: 0.875rem; margin-bottom: 1.5rem;"></p>

            <div class="snippet-form-container">
                <template x-for="field in (activeSnippet?.payload || [])" :key="field.key">
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label class="field-label" style="text-transform: capitalize;" x-text="field.key.replace(/_/g, ' ')"></label>

                        {{-- Se for do tipo Imagem: Usa o seletor padrão de mídia do Lunar Base --}}
                        <template x-if="field.type === 'image'">
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="text" class="form-input" x-model="formValues[field.key]" placeholder="Selecione ou cole a URL da imagem...">
                                <button type="button" class="admin-btn admin-btn-secondary" @click="openMediaSelector(field.key)">
                                    <x-lucide-image class="lucid-icon" /> Mídia
                                </button>
                            </div>
                        </template>

                        {{-- Se for texto comum --}}
                        <template x-if="field.type !== 'image'">
                            <input type="text" class="form-input" :placeholder="'Digite o valor para ' + field.key + '...'" x-model="formValues[field.key]">
                        </template>
                    </div>
                </template>

                <div x-show="!activeSnippet?.payload || activeSnippet.payload.length === 0" style="padding: 2rem; text-align: center; color: #64748b; font-style: italic;">
                    Este molde é estático e não possui variáveis mapeadas. Você pode inseri-lo diretamente.
                </div>
            </div>

            <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" @click="insertIntoEditor()" class="admin-btn admin-btn-primary">
                    <x-lucide-check class="lucid-icon" /> Inserir no Editor
                </button>
            </div>
        </div>

    </div>
</x-modal>

<style>
    .snippet-picker-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 1.25rem;
    }
    .snippet-picker-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
    }
    .snippet-picker-card:hover {
        transform: translateY(-2px);
        border-color: #3b82f6;
        box-shadow: 0 8px 12px -3px rgba(0,0,0,0.05);
    }
    .snippet-picker-thumb {
        width: 100%;
        height: 120px;
        background: #f1f5f9;
        border-bottom: 1px solid #e2e8f0;
        overflow: hidden;
    }
    .snippet-picker-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .snippet-picker-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        color: #94a3b8;
        font-size: 0.75rem;
    }
    .snippet-picker-body {
        padding: 0.85rem;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }
    .snippet-picker-title {
        margin: 0 0 4px 0;
        font-size: 0.95rem;
        font-weight: 600;
        color: #1e293b;
    }
    .snippet-picker-desc {
        margin: 0 0 8px 0;
        font-size: 0.75rem;
        color: #64748b;
        line-height: 1.3;
    }
    .snippet-picker-slug {
        margin-top: auto;
        font-size: 0.7rem;
        background: #f8fafc;
        padding: 2px 6px;
        border-radius: 4px;
        color: #475569;
    }
</style>

<script>
// function snippetPickerInserter() {
//     return {
//         snippets: window.LUNAR_VISUAL_SNIPPETS || [],
//         searchQuery: '',
//         step: 'gallery', // 'gallery' ou 'form'
//         activeSnippet: null,
//         formValues: {},
//         activeImageFieldKey: null,

//         init() {
//             // Escuta o evento global para abrir o modal quando o usuário clicar no botão do Tiptap
//             window.addEventListener('visual-snippets:open-modal', () => {
//                 this.step = 'gallery';
//                 this.searchQuery = '';
//                 this.activeSnippet = null;
//                 this.formValues = {};
//                 window.dispatchEvent(new CustomEvent('modal-open', { detail: { id: 'visualSnippetPickerModal' } }));
//             });

//             // Escuta o retorno do modal padrão de mídia do Lunar Base
//             window.addEventListener('media:inserted', (e) => {
//                 if (e.detail?.source === 'visual-snippets-media-picker' && this.activeImageFieldKey) {
//                     this.formValues[this.activeImageFieldKey] = e.detail.media.url;
//                     window.dispatchEvent(new CustomEvent('modal-close', { detail: { id: 'selectorModal' } }));
//                     this.activeImageFieldKey = null;
//                 }
//             });
//         },

//         get filteredSnippets() {
//             if (!this.searchQuery) return this.snippets;
//             const q = this.searchQuery.toLowerCase();
//             return this.snippets.filter(s => s.title.toLowerCase().includes(q) || s.slug.toLowerCase().includes(q));
//         },

//         selectSnippet(snippet) {
//             this.activeSnippet = snippet;
//             this.formValues = {};

//             // Se o snippet não tiver variáveis, insere direto sem precisar ir para o passo 2
//             if (!snippet.payload || snippet.payload.length === 0) {
//                 this.insertDirectly(snippet.slug);
//                 return;
//             }

//             this.step = 'form';
//         },

//         openMediaSelector(fieldKey) {
//             this.activeImageFieldKey = fieldKey;
//             if (typeof window.openGridModal === 'function') {
//                 window.openGridModal('visual-snippets-media-picker', false, [], { title: 'Selecionar Imagem para o Snippet' });
//             }
//         },

//         insertDirectly(slug) {
//             const shortcode = `[vs-template slug="${slug}"]`;
//             window.dispatchEvent(new CustomEvent('visual-snippets:insert-code', { detail: { code: shortcode } }));
//             window.dispatchEvent(new CustomEvent('modal-close', { detail: { id: 'visualSnippetPickerModal' } }));
//         },

//         insertIntoEditor() {
//             if (!this.activeSnippet) return;

//             let attrs = `slug="${this.activeSnippet.slug}"`;
//             const payload = this.activeSnippet.payload || [];

//             payload.forEach(field => {
//                 const val = this.formValues[field.key] || '';
//                 if (val.trim() !== '') {
//                     const safeVal = val.replace(/"/g, '&quot;');
//                     attrs += ` ${field.key}="${safeVal}"`;
//                 }
//             });

//             const shortcode = `[vs-instance ${attrs}]`;

//             // Dispara evento para o editor Tiptap capturar e inserir
//             window.dispatchEvent(new CustomEvent('visual-snippets:insert-code', { detail: { code: shortcode } }));
//             window.dispatchEvent(new CustomEvent('modal-close', { detail: { id: 'visualSnippetPickerModal' } }));
//         }
//     }
// }

function snippetPickerInserter() {
    return {
        snippets: window.LUNAR_VISUAL_SNIPPETS || [],
        searchQuery: '',
        step: 'gallery', // 'gallery' ou 'form'
        activeSnippet: null,
        formValues: {},
        activeImageFieldKey: null,

        init() {
            // Escuta o evento global para abrir o modal em tela cheia (full)
            window.addEventListener('visual-snippets:open-modal', () => {
                this.step = 'gallery';
                this.searchQuery = '';
                this.activeSnippet = null;
                this.formValues = {};

                const modalBox = this.$el.closest('.modal-box');
                if (modalBox) {
                    modalBox.classList.remove('full', 'lg', 'xl', 'sm');
                    modalBox.classList.add('full');
                }

                window.dispatchEvent(new CustomEvent('modal-set-title', { detail: { id: 'visualSnippetPickerModal', title: 'Inserir Snippet Visual' } }));
                window.dispatchEvent(new CustomEvent('modal-open', { detail: { id: 'visualSnippetPickerModal' } }));
            });

            // Escuta o retorno do modal padrão de mídia do Lunar Base
            window.addEventListener('media:inserted', (e) => {
                if (e.detail?.source === 'visual-snippets-media-picker' && this.activeImageFieldKey) {
                    this.formValues[this.activeImageFieldKey] = e.detail.media.url;
                    window.dispatchEvent(new CustomEvent('modal-close', { detail: { id: 'selectorModal' } }));
                    this.activeImageFieldKey = null;
                }
            });
        },

        get filteredSnippets() {
            if (!this.searchQuery) return this.snippets;
            const q = this.searchQuery.toLowerCase();
            return this.snippets.filter(s => s.title.toLowerCase().includes(q) || s.slug.toLowerCase().includes(q));
        },

        selectSnippet(snippet) {
            this.activeSnippet = snippet;
            this.formValues = {};

            // Se o snippet não tiver variáveis, insere direto
            if (!snippet.payload || snippet.payload.length === 0) {
                this.insertDirectly(snippet.slug);
                return;
            }

            // Transição para o Passo 2: Formulário (Muda o modal para tamanho MD)
            this.step = 'form';

            const modalBox = this.$el.closest('.modal-box');
            if (modalBox) {
                modalBox.classList.remove('full', 'lg', 'xl', 'sm');
                modalBox.classList.add('md');
            }

            window.dispatchEvent(new CustomEvent('modal-set-title', {
                detail: { id: 'visualSnippetPickerModal', title: 'Preencher: ' + snippet.title }
            }));
        },

        goToGallery() {
            this.step = 'gallery';

            // Volta o modal para tamanho FULL (Galeria)
            const modalBox = this.$el.closest('.modal-box');
            if (modalBox) {
                modalBox.classList.remove('md', 'lg', 'xl', 'sm');
                modalBox.classList.add('full');
            }

            window.dispatchEvent(new CustomEvent('modal-set-title', {
                detail: { id: 'visualSnippetPickerModal', title: 'Inserir Snippet Visual' }
            }));
        },

        openMediaSelector(fieldKey) {
            this.activeImageFieldKey = fieldKey;
            if (typeof window.openGridModal === 'function') {
                window.openGridModal('visual-snippets-media-picker', false, [], { title: 'Selecionar Imagem para o Snippet' });
            }
        },

        insertDirectly(slug) {
            const shortcode = `[vs-template slug="${slug}"]`;
            window.dispatchEvent(new CustomEvent('visual-snippets:insert-code', { detail: { code: shortcode } }));
            window.dispatchEvent(new CustomEvent('modal-close', { detail: { id: 'visualSnippetPickerModal' } }));
        },

        insertIntoEditor() {
            if (!this.activeSnippet) return;

            let attrs = `slug="${this.activeSnippet.slug}"`;
            const payload = this.activeSnippet.payload || [];

            payload.forEach(field => {
                const val = this.formValues[field.key] || '';
                if (val.trim() !== '') {
                    const safeVal = val.replace(/"/g, '&quot;');
                    attrs += ` ${field.key}="${safeVal}"`;
                }
            });

            const shortcode = `[vs-instance ${attrs}]`;

            window.dispatchEvent(new CustomEvent('visual-snippets:insert-code', { detail: { code: shortcode } }));
            window.dispatchEvent(new CustomEvent('modal-close', { detail: { id: 'visualSnippetPickerModal' } }));
        }
    }
}
</script>
