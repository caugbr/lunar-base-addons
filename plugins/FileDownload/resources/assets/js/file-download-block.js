/**
 * Plugin File Download para o Lunar Base Editor
 */
(function() {
  'use strict';

  if (!window.LunarEditor) return;

  const { Node, mergeAttributes, VueNodeViewRenderer, NodeViewWrapper, Vue } = window.LunarEditor;
  const { h } = Vue;

  let activeNodeUpdater = null;

  // Extrai a extensão do arquivo a partir da URL
  const getExtensionFromUrl = (url) => {
    if (!url) return 'FILE';
    const cleanUrl = url.split('?')[0].split('#')[0];
    const ext = cleanUrl.split('.').pop();
    return ext && ext.length <= 4 ? ext.toUpperCase() : 'FILE';
  };

  // Escuta a inserção tanto da BIBLIOTECA quanto do UPLOAD DO COMPUTADOR
  window.addEventListener('media:inserted', (e) => {
    if (e.detail?.source === 'file-download-plugin' && activeNodeUpdater) {
      const media = e.detail.media;
      const fileUrl = media.url || '';
      const ext = media.extension ? media.extension.toUpperCase() : getExtensionFromUrl(fileUrl);
      const originalName = media.title || media.name || fileUrl.split('/').pop() || 'Arquivo para Download';
      const size = media.human_size || media.size || '';

      activeNodeUpdater({
        fileUrl: fileUrl,
        fileName: originalName,
        fileSize: size,
        fileExt: ext
      });

      // Fecha qualquer modal que esteja aberto (Seletor ou Uploader)
      window.dispatchEvent(new CustomEvent('modal-close', { detail: { id: 'selectorModal' } }));
      window.dispatchEvent(new CustomEvent('modal-close', { detail: { id: 'mainUploader' } }));

      activeNodeUpdater = null;
    }
  });

  // Ícones SVG do Lucide
  const iconFile = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-down"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M12 18v-6"/><path d="m9 15 3 3 3-3"/></svg>';
  const iconUpload = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-upload"><path d="m21 15-9-9-9 9"/><path d="M12 6v15"/></svg>';
  const iconLibrary = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-folder-open"><path d="m6 14 1.5-2.9A2 2 0 0 1 9.24 10H20a2 2 0 0 1 1.94 2.5l-1.54 6a2 2 0 0 1-1.95 1.5H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3.9a2 2 0 0 1 1.69.9l.81 1.2a2 2 0 0 0 1.67.9H18a2 2 0 0 1 2 2v2"/></svg>';
  const iconTrash = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash"><path d="M10 11v6"/><path d="M14 11v6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';
  const iconDownload = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-download"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" y2="3"/></svg>';

  // COMPONENTE VUE INTERATIVO NO EDITOR
  const FileDownloadComponent = {
    props: ['node', 'updateAttributes', 'deleteNode'],
    setup(props) {
      // 1. AÇÃO: Abrir Biblioteca de Mídia
      const openMediaLibrary = () => {
        activeNodeUpdater = props.updateAttributes;

        if (typeof window.openGridModal === 'function') {
          window.openGridModal(
            'file-download-plugin',
            false,
            props.node.attrs.fileUrl ? [props.node.attrs.fileUrl] : [],
            { title: 'Selecionar Arquivo na Biblioteca' }
          );
        } else {
          window.dispatchEvent(new CustomEvent('modal-open', {
            detail: { id: 'selectorModal', context: 'file-download-plugin' }
          }));
        }
      };

      // 2. AÇÃO: Upload direto do Computador
      const openDirectUpload = () => {
        activeNodeUpdater = props.updateAttributes;

        window.dispatchEvent(new CustomEvent('media:upload-open', {
          detail: { id: 'mainUploader', context: 'file-download-plugin' }
        }));
      };

      const updateFileName = (e) => props.updateAttributes({ fileName: e.target.value });
      const updateButtonText = (e) => props.updateAttributes({ buttonText: e.target.value });

      return () => h(NodeViewWrapper, { class: 'lunar-file-editor-wrapper' }, [
        h('div', { class: 'lunar-file-admin-box', contenteditable: 'false' }, [

          // Barra Superior do Admin
          h('div', { class: 'lunar-file-admin-bar' }, [
            h('span', { class: 'lunar-file-admin-label' }, [
              h('span', { innerHTML: iconFile }),
              h('span', 'CARD DE ARQUIVO PARA DOWNLOAD')
            ]),
            h('div', { class: 'lunar-file-admin-actions' }, [
              props.node.attrs.fileUrl
                ? h('div', { class: 'actions-btn-group' }, [
                    h('button', {
                      type: 'button',
                      class: 'lunar-file-btn-sm',
                      onClick: openMediaLibrary,
                      title: 'Trocar pela Biblioteca'
                    }, [h('span', { innerHTML: iconLibrary }), h('span', 'Biblioteca')]),
                    h('button', {
                      type: 'button',
                      class: 'lunar-file-btn-sm',
                      onClick: openDirectUpload,
                      title: 'Enviar novo do Computador'
                    }, [h('span', { innerHTML: iconUpload }), h('span', 'Computador')])
                  ])
                : null,
              h('button', {
                type: 'button',
                class: 'lunar-file-delete-btn',
                onClick: () => props.deleteNode(),
                title: 'Remover Bloco'
              }, [h('span', { innerHTML: iconTrash })])
            ])
          ]),

          // ESTADO VAZIO: Dois botões de escolha
          !props.node.attrs.fileUrl
            ? h('div', { class: 'lunar-file-empty-box' }, [
                h('span', { class: 'empty-main-icon', innerHTML: iconFile }),
                h('strong', 'Adicionar Arquivo para Download'),
                h('p', 'Escolha como deseja anexar o arquivo (PDF, DOCX, ZIP, etc.):'),
                h('div', { class: 'lunar-file-choice-buttons' }, [
                  h('button', {
                    type: 'button',
                    class: 'btn-choice btn-choice-library',
                    onClick: openMediaLibrary
                  }, [
                    h('span', { innerHTML: iconLibrary }),
                    h('span', 'Selecionar na Biblioteca')
                  ]),
                  h('button', {
                    type: 'button',
                    class: 'btn-choice btn-choice-upload',
                    onClick: openDirectUpload
                  }, [
                    h('span', { innerHTML: iconUpload }),
                    h('span', 'Buscar no Computador')
                  ])
                ])
              ])

          // ESTADO PREENCHIDO: Card visual
            : h('div', { class: 'lunar-file-card-preview' }, [
                h('div', { class: 'lunar-file-badge' }, props.node.attrs.fileExt || 'PDF'),
                h('div', { class: 'lunar-file-info' }, [
                  h('input', {
                    type: 'text',
                    value: props.node.attrs.fileName,
                    onInput: updateFileName,
                    placeholder: 'Título / Descrição do Arquivo...',
                    class: 'lunar-file-input-name'
                  }),
                  h('div', { class: 'lunar-file-meta' }, [
                    h('span', { class: 'meta-tag' }, `Formato: ${props.node.attrs.fileExt}`),
                    props.node.attrs.fileSize
                      ? h('span', { class: 'meta-tag' }, `Tamanho: ${props.node.attrs.fileSize}`)
                      : null
                  ])
                ]),
                h('div', { class: 'lunar-file-cta' }, [
                  h('div', { class: 'cta-button-mock' }, [
                    h('span', { innerHTML: iconDownload }),
                    h('input', {
                      type: 'text',
                      value: props.node.attrs.buttonText || 'Baixar',
                      onInput: updateButtonText,
                      class: 'lunar-file-input-btn',
                      title: 'Editar texto do botão'
                    })
                  ])
                ])
              ])

        ])
      ]);
    }
  };

  // EXTENSÃO TIPTAP
  const FileDownloadExtension = Node.create({
    name: 'fileDownload',
    group: 'block',
    atom: true,
    draggable: true,

    addAttributes() {
      return {
        fileUrl: { default: '' },
        fileName: { default: 'Arquivo para Download' },
        fileSize: { default: '' },
        fileExt: { default: 'PDF' },
        buttonText: { default: 'Baixar Arquivo' }
      };
    },

    parseHTML() {
      return [{
        tag: 'div[data-type="file-download"]',
        getAttrs: (element) => ({
          fileUrl: element.getAttribute('data-url') || '',
          fileName: element.getAttribute('data-name') || 'Arquivo para Download',
          fileSize: element.getAttribute('data-size') || '',
          fileExt: element.getAttribute('data-ext') || 'PDF',
          buttonText: element.getAttribute('data-btn') || 'Baixar Arquivo'
        })
      }];
    },

    renderHTML({ HTMLAttributes }) {
      const { fileUrl, fileName, fileSize, fileExt, buttonText } = HTMLAttributes;
      const ext = fileExt || 'PDF';

      return [
        'div',
        mergeAttributes(HTMLAttributes, {
          class: 'lunar-file-card',
          'data-type': 'file-download',
          'data-url': fileUrl || '',
          'data-name': fileName || '',
          'data-size': fileSize || '',
          'data-ext': ext,
          'data-btn': buttonText || 'Baixar Arquivo'
        }),
        ['div', { class: 'lunar-file-badge' }, ext],
        [
          'div',
          { class: 'lunar-file-details' },
          ['strong', { class: 'lunar-file-title' }, fileName || 'Documento para Download'],
          [
            'div',
            { class: 'lunar-file-meta' },
            ['span', {}, ext],
            fileSize ? ['span', { class: 'meta-dot' }, '•'] : '',
            fileSize ? ['span', {}, fileSize] : ''
          ]
        ],
        [
          'a',
          {
            href: fileUrl || '#',
            target: '_blank',
            download: '',
            class: 'lunar-file-download-btn'
          },
          buttonText || 'Baixar Arquivo'
        ]
      ];
    },

    addNodeView() {
      return VueNodeViewRenderer(FileDownloadComponent);
    }
  });

  // REGISTRO NO MEGA-MENU
  window.LunarEditor.registerBlock({
    name: 'fileDownload',
    category: 'Mídia & Conteúdo',
    title: 'Arquivo para Download',
    description: 'Card para download de PDF, documentos e arquivos',
    icon: iconFile,
    extension: FileDownloadExtension,
    action: (editor) => {
      editor.chain().focus().insertContent({
        type: 'fileDownload',
        attrs: {
          fileUrl: '',
          fileName: 'Documento para Download',
          fileSize: '',
          fileExt: 'PDF',
          buttonText: 'Baixar Arquivo'
        }
      }).run();
    }
  });

})();
