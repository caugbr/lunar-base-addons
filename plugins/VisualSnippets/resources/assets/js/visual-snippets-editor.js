/**
 * Plugin Visual Snippets - Extensão de Autoria para o Lunar Editor
 */
(function () {
    'use strict';

    if (!window.LunarEditor) return;

    // Ícone Alvo / Mira do Lucide em SVG inline
    const targetIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-crosshair"><circle cx="12" cy="12" r="10"/><line x1="22" x2="18" y1="12" y2="12"/><line x1="6" x2="2" y1="12" y2="12"/><line x1="12" x2="12" y1="2" y2="6"/><line x1="12" x2="12" y1="18" y2="22"/></svg>`;

    let isInspectMode = false;
    let activeEditorInstance = null;

    // =========================================================================
    // SINCRONIZAÇÃO DE PAYLOAD E INPUTS OCULTOS
    // =========================================================================
    const syncPayloadInput = () => {
        const input = document.getElementById('snippet_payload');
        const editorElement = document.querySelector('.lunar-editor-wrapper .tiptap');
        if (!input || !editorElement) return;

        const variables = [];
        const elementsWithVar = editorElement.querySelectorAll('[data-var]');

        elementsWithVar.forEach(el => {
            const key = el.getAttribute('data-var');
            const tagName = el.tagName.toLowerCase();
            let type = 'text';

            if (tagName === 'img') type = 'image';
            else if (tagName === 'a') type = 'link';

            if (key && !variables.some(v => v.key === key)) {
                variables.push({ key, type, tag: tagName });
            }
        });

        input.value = JSON.stringify(variables);
    };

    const syncEditorInputs = (editor) => {
        const htmlInput = document.getElementById('lunar_content_html');
        const jsonInput = document.getElementById('lunar_content_json');
        const editorDom = document.querySelector('.lunar-editor-wrapper .tiptap');

        if (htmlInput && editorDom) {
            htmlInput.value = editorDom.innerHTML;
        }

        if (jsonInput && editor && typeof editor.getJSON === 'function') {
            try {
                jsonInput.value = JSON.stringify(editor.getJSON());
            } catch (err) {
                console.warn('[VisualSnippets] Falha ao extrair JSON do editor:', err);
            }
        }
    };

    // =========================================================================
    // ATUALIZAÇÃO NO PROSEMIRROR VIA TRANSAÇÃO
    // =========================================================================
    const applyVarToProseMirror = (editor, domTarget, varName) => {
        if (!editor?.view) return false;

        try {
            const view = editor.view;
            const pos = view.posAtDOM(domTarget, 0);
            const $pos = view.state.doc.resolve(pos);

            // Procura o nó correspondente na profundidade da árvore
            for (let depth = $pos.depth; depth >= 0; depth--) {
                const node = $pos.node(depth);
                const nodePos = depth > 0 ? $pos.before(depth) : 0;

                if (node?.type?.spec?.attrs && 'data-var' in node.type.spec.attrs) {
                    const tr = view.state.tr;
                    const nextAttrs = { ...node.attrs, 'data-var': varName || null };
                    tr.setNodeMarkup(nodePos, undefined, nextAttrs);
                    view.dispatch(tr);
                    return true;
                }
            }
        } catch (e) {
            // Se o ProseMirror não tiver o nó mapeado ou for um elemento customizado, fallback para o DOM
        }

        return false;
    };

    // =========================================================================
    // CONTROLE DO MODO DE INSPEÇÃO (LIGAR / DESLIGAR)
    // =========================================================================
    const toggleInspectMode = (editor) => {
        activeEditorInstance = editor;
        isInspectMode = !isInspectMode;

        const wrapper = document.querySelector('.lunar-editor-wrapper');
        const toolbarBtn = document.querySelector('button[title*="Mapear Variáveis"]');

        if (isInspectMode) {
            if (wrapper) wrapper.classList.add('lunar-inspecting-mode');
            if (toolbarBtn) toolbarBtn.classList.add('is-active');
            editor.setEditable(false); // Desativa cursor de digitação para priorizar inspeção
        } else {
            if (wrapper) wrapper.classList.remove('lunar-inspecting-mode');
            if (toolbarBtn) toolbarBtn.classList.remove('is-active');
            editor.setEditable(true); // Restaura edição normal
        }
    };

    // Atalho: Tecla ESC sai do modo de inspeção
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isInspectMode && activeEditorInstance) {
            toggleInspectMode(activeEditorInstance);
        }
    });

    // =========================================================================
    // LISTENER DE CLIQUE PARA CAPTURA DE VARIÁVEIS
    // =========================================================================
    document.addEventListener('click', async (e) => {
        if (!isInspectMode || !activeEditorInstance) return;

        // Se clicou no botão de toggle da Toolbar, ignora
        if (e.target.closest('button[title*="Mapear Variáveis"]')) {
            return;
        }

        const editorDom = document.querySelector('.lunar-editor-wrapper .tiptap');
        if (!editorDom || !editorDom.contains(e.target)) {
            return; // Bloqueia cliques fora da área de conteúdo editável
        }

        // Elementos elegíveis para virar variável
        const target = e.target.closest('p, h1, h2, h3, h4, h5, h6, blockquote, img, a, figure, button, li');
        if (!target) return;

        e.preventDefault();
        e.stopPropagation();

        const currentVar = target.getAttribute('data-var') || '';
        const isEditing = Boolean(currentVar);

        let wasCheckboxChecked = false;
        const handleCloseEvent = (data) => {
            if (data?.id === 'snippet-var-dialog') {
                wasCheckboxChecked = Boolean(data.checked);
            }
        };

        if (window.Dialog && typeof window.Dialog.on === 'function') {
            window.Dialog.on('close', handleCloseEvent);
        }

        let varNameResult = null;
        try {
            if (window.Dialog && typeof window.Dialog.show === 'function') {
                varNameResult = await window.Dialog.show({
                    id: 'snippet-var-dialog',
                    type: 'prompt',
                    title: isEditing ? 'Editar Variável' : 'Mapear Variável',
                    message: isEditing
                        ? 'Altere o nome da variável ou marque para remover:'
                        : 'Digite o identificador único para esta área (ex: titulo_card, foto):',
                    defaultValue: currentVar,
                    confirmText: 'Salvar',
                    cancelText: 'Cancelar',
                    showCheckbox: isEditing,
                    checkboxText: 'Remover variável deste elemento'
                });
            } else {
                // Fallback caso a lib Dialog não esteja carregada
                varNameResult = prompt('Identificador da variável:', currentVar);
            }
        } finally {
            // Remove o listener para evitar memory leak e disparos múltiplos
            if (window.Dialog && typeof window.Dialog.off === 'function') {
                window.Dialog.off('close', handleCloseEvent);
            }
        }

        // Usuário cancelou ou fechou no X/Esc
        if (varNameResult === null) return;

        // Limpeza e normalização do identificador (snake_case seguro)
        const cleanVarName = varNameResult
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9_]/g, '_')
            .replace(/^_+|_+$/g, '');

        const shouldRemove = wasCheckboxChecked || cleanVarName === '';

        if (shouldRemove) {
            target.removeAttribute('data-var');
            applyVarToProseMirror(activeEditorInstance, target, null);
        } else {
            target.setAttribute('data-var', cleanVarName);
            applyVarToProseMirror(activeEditorInstance, target, cleanVarName);
        }

        // Sincroniza payload e inputs de formulário
        syncPayloadInput();
        syncEditorInputs(activeEditorInstance);

        window.dispatchEvent(new CustomEvent('editor:dirty'));
    }, true);

    // =========================================================================
    // REGISTRO NA TOOLBAR E OBSERVER DE CICLO DE VIDA DO EDITOR
    // =========================================================================
    window.LunarEditor.registerToolbarButton({
        name: 'visual_snippets_mapper',
        title: 'Mapear Variáveis (Definir Slots Editáveis)',
        icon: targetIcon,
        isActive: () => isInspectMode,
        action: (editor) => {
            // Observa alterações no editor para atualizar o payload automaticamente se o redator deletar blocos
            if (editor && typeof editor.on === 'function' && !editor._vsPayloadListenerAttached) {
                editor.on('update', () => {
                    syncPayloadInput();
                    syncEditorInputs(editor);
                });
                editor._vsPayloadListenerAttached = true;
            }

            toggleInspectMode(editor);
        }
    });

})();
