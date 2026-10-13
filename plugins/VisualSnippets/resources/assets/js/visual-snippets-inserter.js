/**
 * Plugin Visual Snippets - Ponte do Inseridor para o Tiptap
 */
(function () {
    'use strict';

    if (!window.LunarEditor) return;

    const templateIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-template"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>`;

    let activeEditor = null;

    // Escuta quando o modal em Blade manda inserir o shortcode no editor
    window.addEventListener('visual-snippets:insert-code', (e) => {
        const code = e.detail?.code;
        if (code && activeEditor) {
            activeEditor.chain().focus().insertContent(code).run();
        }
    });

    // Registra o bloco no Mega Menu (+ Bloco) do Tiptap
    window.LunarEditor.registerBlock({
        name: 'visualSnippetInserter',
        category: 'Estrutura & Layout',
        title: 'Snippet Visual',
        description: 'Inserir molde com layout reutilizável',
        icon: templateIcon,
        action: (editor) => {
            activeEditor = editor;
            // Dispara o evento para o componente Blade <x-modal> abrir a tela cheia
            window.dispatchEvent(new CustomEvent('visual-snippets:open-modal'));
        }
    });

})();
