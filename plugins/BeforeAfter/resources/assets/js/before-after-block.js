/**
 * Plugin Before / After para o Lunar Base Editor
 */
(function() {
  'use strict';

  if (!window.LunarEditor) return;

  const { Node, mergeAttributes, VueNodeViewRenderer, NodeViewWrapper, Vue } = window.LunarEditor;
  const { h, ref } = Vue;

  let activeNodeUpdater = null;
  let activeNodeAttrs = null;
//   let activeSlotTarget = null;

  // Escuta globalmente a seleção de mídia do modal nativo do Lunar Base
//   window.addEventListener('media:inserted', (e) => {
//     console.log('inserted', e.detail);
//     if (e.detail.source === 'before-after-plugin' && activeNodeUpdater && e.detail?.side) {
//       const mediaUrl = e.detail.media.url;
//       activeNodeUpdater({ [e.detail.side]: mediaUrl });

//       // Fecha o modal de mídia
//       window.dispatchEvent(new CustomEvent('modal-close', { detail: { id: 'selectorModal' } }));
//       activeNodeUpdater = null;
//     //   activeSlotTarget = null;
//     }
//   });
  // Escuta globalmente a seleção de mídia do modal nativo do Lunar Base
  window.addEventListener('media:inserted', (e) => {
    if (e.detail.source === 'before-after-plugin' && activeNodeUpdater && e.detail?.side) {
      const mediaUrl = e.detail.media.url;
      const side = e.detail.side;

      // Descobre qual é o "outro lado" para usar como referência
      const otherSide = side === 'imageBefore' ? 'imageAfter' : 'imageBefore';
      const referenceUrl = activeNodeAttrs[otherSide];

      // Função auxiliar para validar dimensões
      const validateAndInsert = () => {
        const refImg = new Image();
        const newImg = new Image();
        let imagesLoaded = 0;

        const checkDimensions = () => {
          imagesLoaded++;
          if (imagesLoaded === 2) {
            if (newImg.naturalWidth === refImg.naturalWidth && newImg.naturalHeight === refImg.naturalHeight) {
              // ✅ Dimensões iguais! Pode inserir.

              activeNodeUpdater({
                [side]: mediaUrl,
                imageWidth: newImg.naturalWidth,   // <--- ATUALIZA LARGURA
                imageHeight: newImg.naturalHeight  // <--- ATUALIZA ALTURA
              });
            //   activeNodeUpdater({ [side]: mediaUrl });
              window.dispatchEvent(new CustomEvent('modal-close', { detail: { id: 'selectorModal' } }));
              activeNodeUpdater = null;
              activeNodeAttrs = null;
            } else {
              // ❌ Dimensões diferentes! Bloqueia e avisa.
              const msg = `A imagem deve ter exatamente as mesmas dimensões da imagem já selecionada (${refImg.naturalWidth}x${refImg.naturalHeight}).\nA imagem escolhida tem ${newImg.naturalWidth}x${newImg.naturalHeight}.`;

              // Usa o Dialog do seu sistema (visto no grid.blade.php) ou alert nativo
              if (typeof Dialog !== 'undefined' && Dialog.alert) {
                Dialog.alert(msg);
              } else {
                alert(msg);
              }
            }
          }
        };

        refImg.onload = checkDimensions;
        newImg.onload = checkDimensions;

        // Se houver erro no carregamento de alguma imagem, falha com segurança
        refImg.onerror = () => { console.error('Erro ao carregar imagem de referência'); imagesLoaded = 2; };
        newImg.onerror = () => { console.error('Erro ao carregar nova imagem'); imagesLoaded = 2; };

        refImg.src = referenceUrl;
        newImg.src = mediaUrl;
      };

      if (referenceUrl) {
        // Já existe uma imagem no outro slot: valida e insere
        validateAndInsert();
      } else {
        // É a primeira imagem: carrega invisivelmente para descobrir as dimensões reais
        const firstImg = new Image();
        firstImg.onload = () => {
          activeNodeUpdater({
            [side]: mediaUrl,
            imageWidth: firstImg.naturalWidth,
            imageHeight: firstImg.naturalHeight
          });
          window.dispatchEvent(new CustomEvent('modal-close', { detail: { id: 'selectorModal' } }));
          activeNodeUpdater = null;
          activeNodeAttrs = null;
        };
        firstImg.onerror = () => {
          // Fallback de segurança
          activeNodeUpdater({ [side]: mediaUrl });
          window.dispatchEvent(new CustomEvent('modal-close', { detail: { id: 'selectorModal' } }));
          activeNodeUpdater = null;
          activeNodeAttrs = null;
        };
        firstImg.src = mediaUrl;
      }
    }
  });

  const deleteIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash"><path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';

  // COMPONENTE VUE INTERATIVO NO ADMIN
  const BeforeAfterComponent = {
    props: ['node', 'updateAttributes', 'deleteNode'],
    setup(props) {
      const openMediaSelector = (slot) => {
        activeNodeUpdater = props.updateAttributes;
        activeNodeAttrs = props.node.attrs;

        openGridModal('before-after-plugin', true, [], {}, { side: slot });
      };

      return () => h(NodeViewWrapper, { class: 'lunar-ba-editor-wrapper' }, [
        h('div', { class: 'lunar-ba-box', contenteditable: 'false' }, [

          // Barra Superior do Admin
          h('div', { class: 'lunar-ba-admin-bar' }, [
            h('span', { class: 'lunar-ba-admin-label' }, [
              h('span', { innerHTML: iconSvg }),
              h('span', 'COMPARADOR ANTES / DEPOIS')
            ]),
            h('button', {
                type: 'button',
                class: 'lunar-ba-delete-btn',
                onClick: () => props.deleteNode(),
                title: 'Remover Bloco'
            }, [
                h('span', { innerHTML: deleteIcon })
            ])
          ]),

          // Slots de Seleção das Imagens
          h('div', { class: 'lunar-ba-slots' }, [
            // Slot ANTES
            h('div', { class: 'lunar-ba-slot' }, [
              props.node.attrs.imageBefore
                ? h('div', { class: 'lunar-ba-preview' }, props.node.attrs.imageBefore.split('/').at(-1))
                : h('div', { class: 'lunar-ba-placeholder' }, 'Nenhuma imagem'),
              h('button', {
                type: 'button',
                class: 'lunar-ba-select-btn',
                onClick: () => openMediaSelector('imageBefore')
              }, 'Selecionar "Antes"')
            ]),

            // Slot DEPOIS
            h('div', { class: 'lunar-ba-slot' }, [
              props.node.attrs.imageAfter
                ? h('div', { class: 'lunar-ba-preview' }, props.node.attrs.imageAfter.split('/').at(-1))
                : h('div', { class: 'lunar-ba-placeholder' }, 'Nenhuma imagem'),
              h('button', {
                type: 'button',
                class: 'lunar-ba-select-btn',
                onClick: () => openMediaSelector('imageAfter')
              }, 'Selecionar "Depois"')
            ])
          ]),

          // Pré-visualização interativa no Admin
          h('div', { class: 'lunar-ba-preview-container' }, [
            props.node.attrs.imageBefore
              ? h('img', { src: props.node.attrs.imageBefore, class: 'lunar-ba-preview-img lunar-ba-preview-before' })
              : null,
            props.node.attrs.imageAfter
              ? h('img', { src: props.node.attrs.imageAfter, class: 'lunar-ba-preview-img lunar-ba-preview-after' })
              : null,
            h('span', { class: 'lunar-ba-labels lunar-ba-label-before' }, props.node.attrs.labelBefore || 'Antes'),
            h('span', { class: 'lunar-ba-labels lunar-ba-label-after' }, props.node.attrs.labelAfter || 'Depois')
          ])

        ])
      ]);
    }
  };

  // EXTENSÃO TIPTAP
  const BeforeAfterExtension = Node.create({
    name: 'beforeAfter',
    group: 'block',
    atom: true,
    draggable: true,

    addAttributes() {
      return {
        imageBefore: { default: '' },
        imageAfter: { default: '' },
        labelBefore: { default: 'Antes' },
        labelAfter: { default: 'Depois' },
        imageWidth: { default: null },  // <--- NOVO
        imageHeight: { default: null },  // <--- NOVO
        aspectRatio: { default: '16 / 9' }
      };
    },

    parseHTML() {
      return [{
        tag: 'div[data-type="before-after"]',
        getAttrs: (element) => ({
          imageBefore: element.getAttribute('data-img-before') || '',
          imageAfter: element.getAttribute('data-img-after') || '',
          labelBefore: element.getAttribute('data-label-before') || 'Antes',
          labelAfter: element.getAttribute('data-label-after') || 'Depois',
          imageWidth: parseInt(element.getAttribute('data-img-width')) || null, // <--- NOVO
          imageHeight: parseInt(element.getAttribute('data-img-height')) || null
        })
      }];
    },

    renderHTML({ node, HTMLAttributes }) {
      // ACESSA node.attrs DIRETAMENTE
      const imageWidth = node.attrs.imageWidth;
      const imageHeight = node.attrs.imageHeight;

      // CONVERTE PARA NÚMERO E VALIDA
      const width = parseInt(imageWidth, 10);
      const height = parseInt(imageHeight, 10);

      // FALLBACK GARANTIDO
      const aspectRatio = (width > 0 && height > 0)
        ? `${width} / ${height}`
        : '16 / 9';

      return [
        'div',
        mergeAttributes(HTMLAttributes, {
          class: 'lunar-ba-container',
          'data-type': 'before-after',
          'data-img-before': node.attrs.imageBefore || '',
          'data-img-after': node.attrs.imageAfter || '',
          'data-img-width': width || '',
          'data-img-height': height || '',
          'data-label-before': node.attrs.labelBefore || 'Antes',
          'data-label-after': node.attrs.labelAfter || 'Depois',
          style: `--ba-aspect-ratio: ${aspectRatio};`
        }),
        // Imagem DEPOIS (Fundo)
        ['img', { src: node.attrs.imageAfter || '', class: 'lunar-ba-img lunar-ba-after-wrap', alt: node.attrs.labelAfter }],
        ['div', { class: 'lunar-ba-badge lunar-ba-badge-after' }, node.attrs.labelAfter],

        // Imagem ANTES (Topo com recorte)
        ['div', { class: 'lunar-ba-img lunar-ba-before-wrap', style: 'width: 50%;' },
          ['img', { src: node.attrs.imageBefore || '', alt: node.attrs.labelBefore }]
        ],
        ['div', { class: 'lunar-ba-badge lunar-ba-badge-before' }, node.attrs.labelBefore],

        // Barra Divisora e Range Input invisível
        ['div', { class: 'lunar-ba-handle' },
          ['div', { class: 'lunar-ba-handle-btn' }, '↔']
        ],
        ['input', { type: 'range', min: '0', max: '100', value: '50', class: 'lunar-ba-range' }]
      ];
    },

    addNodeView() {
      return VueNodeViewRenderer(BeforeAfterComponent);
    }
  });


  const iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-columns"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M12 3v18"/></svg>`;
  // REGISTRA O BLOCO NO MEGA-MENU DO EDITOR
  window.LunarEditor.registerBlock({
    name: 'beforeAfter',
    category: 'Mídia & Conteúdo',
    title: 'Antes / Depois',
    description: 'Comparador de imagens interativo com slider',
    // icon: iconSvg,
    icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-columns"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M12 3v18"/></svg>',
    extension: BeforeAfterExtension,
    action: (editor) => {
      editor.chain().focus().insertContent({
        type: 'beforeAfter',
        attrs: {
          imageBefore: '',
          imageAfter: '',
          labelBefore: 'Antes',
          labelAfter: 'Depois'
        }
      }).run();
    }
  });

})();
