/**
 * Plugin Table of Contents para o Lunar Base
 */
(function() {
  'use strict';

  if (!window.LunarEditor) return;

  const { Node, mergeAttributes, VueNodeViewRenderer, NodeViewWrapper, Vue } = window.LunarEditor;
  const { h, computed } = Vue;

  // Função utilitária de Slug
  function slugify(text) {
    return text
      .toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .toLowerCase().trim()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/[\s_-]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  // 1. COMPONENTE VUE DO SUMÁRIO
  const TableOfContentsComponent = {
    props: ['node', 'editor'],
    setup(props) {
      // Varre o documento inteiro em tempo real procurando os títulos
      const headings = computed(() => {
        if (!props.editor) return [];
        const list = [];

        props.editor.state.doc.descendants((node) => {
          if (node.type.name === 'heading') {
            const text = node.textContent;
            if (text && text.trim() !== '') {
              list.push({
                level: node.attrs.level,
                text: text,
                slug: slugify(text)
              });
            }
          }
        });

        return list;
      });

      return () => h(NodeViewWrapper, { class: 'lunar-toc-wrapper' }, [
        h('div', { class: 'lunar-toc-box', contenteditable: 'false' }, [
          // Cabeçalho do Sumário
          h('div', { class: 'lunar-toc-header' }, [
            h('span', '📑'),
            h('span', props.node.attrs.title || 'Índice de Conteúdo')
          ]),

          // Lista de Títulos encontrados
          headings.value.length > 0
            ? h('ul', { class: 'lunar-toc-list' },
                headings.value.map((heading) =>
                  h('li', { class: `lunar-toc-item level-${heading.level}` }, [
                    h('a', { href: `#${heading.slug}` }, heading.text)
                  ])
                )
              )
            : h('p', { class: 'lunar-toc-empty' }, 'Nenhum título (H1, H2, H3) encontrado no texto ainda...')
        ])
      ]);
    }
  };

  // 2. EXTENSÃO TIPTAP
  const TableOfContentsExtension = Node.create({
    name: 'tableOfContents',
    group: 'block',
    atom: true, // Bloco atômico (se auto-atualiza dinamicamente)
    draggable: true,

    addAttributes() {
      return {
        title: { default: 'Índice de Conteúdo' }
      };
    },

    parseHTML() {
      return [{ tag: 'nav[data-type="table-of-contents"]' }];
    },

    renderHTML({ HTMLAttributes }) {
      return [
        'nav',
        mergeAttributes(HTMLAttributes, {
          class: 'lunar-toc-box',
          'data-type': 'table-of-contents'
        }),
        ['div', { class: 'lunar-toc-header' }, '📑 ' + (HTMLAttributes.title || 'Índice de Conteúdo')],
        ['div', { class: 'lunar-toc-placeholder' }, ''] // O front-end ou leitor consome o nav
      ];
    },

    addNodeView() {
      return VueNodeViewRenderer(TableOfContentsComponent);
    }
  });

  // 3. REGISTRA O BLOCO NO LUNAR BASE!
  window.LunarEditor.registerBlock({
    name: 'tableOfContents',
    category: 'Estrutura & Layout', // Entra na primeira coluna do Mega-Menu!
    title: 'Sumário Automático',
    description: 'Índice navegável baseado nos títulos',
    extension: TableOfContentsExtension,
    action: (editor) => {
      editor.chain().focus().insertContent({
        type: 'tableOfContents',
        attrs: { title: 'Índice de Conteúdo' }
      }).run();
    }
  });

})();
