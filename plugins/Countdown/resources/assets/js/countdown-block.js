/**
 * Plugin Countdown para o Lunar Base
 */
(function() {
  'use strict';

  if (!window.LunarEditor) return;

  const { Node, mergeAttributes, VueNodeViewRenderer, NodeViewWrapper, Vue } = window.LunarEditor;
  const { h, ref, computed } = Vue;

  // Ícone Timer do Lucide
  const timerIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-timer"><line x1="10" x2="14" y1="2" y2="2"/><line x1="12" x2="15" y1="14" y2="11"/><circle cx="12" cy="14" r="8"/></svg>`;

  // 1. COMPONENTE VUE INTERATIVO NO ADMIN
  const CountdownComponent = {
    props: ['node', 'updateAttributes', 'deleteNode'],
    setup(props) {
      // Data padrão: 7 dias a partir de hoje se não tiver data definida
      const defaultDate = () => {
        const d = new Date();
        d.setDate(d.getDate() + 7);
        return d.toISOString().slice(0, 16);
      };

      const targetDate = computed(() => props.node.attrs.targetDate || defaultDate());

      return () => h(NodeViewWrapper, { class: 'lunar-countdown-editor-wrapper' }, [
        h('div', { class: `lunar-countdown-box theme-${props.node.attrs.theme || 'dark'}` }, [

          // Barra de Configuração do Admin (No topo do bloco)
          h('div', { class: 'countdown-admin-bar', contenteditable: 'false' }, [
            h('span', { class: 'countdown-admin-label' }, [
              h('span', { innerHTML: timerIcon }),
              h('span', 'CONTADOR REGRESSIVO')
            ]),

            // Controles
            h('div', { class: 'countdown-admin-controls' }, [
              // Input de Data e Hora
              h('input', {
                type: 'datetime-local',
                class: 'countdown-date-input',
                value: targetDate.value,
                onChange: (e) => props.updateAttributes({ targetDate: e.target.value })
              }),

              // Seletor de Tema
              h('select', {
                  class: 'countdown-theme-select',
                  value: props.node.attrs.theme || 'light',
                  onChange: (e) => props.updateAttributes({ theme: e.target.value })
              }, [
                  h('option', { value: 'light' }, 'Claro'),
                  h('option', { value: 'dark' }, 'Escuro'),
                  h('option', { value: 'blue' }, 'Azul')
              ]),

              // Botão Excluir
              h('button', {
                type: 'button',
                class: 'btn-delete-countdown',
                onClick: () => props.deleteNode(),
                title: 'Remover Contador'
              }, '✕')
            ])
          ]),

          // Título do Contador Editável
          h('div', { class: 'countdown-title-wrap' }, [
            h('input', {
              type: 'text',
              class: 'countdown-title-input',
              value: props.node.attrs.title,
              placeholder: 'Ex: A oferta termina em...',
              onInput: (e) => props.updateAttributes({ title: e.target.value })
            })
          ]),

          // Visualização dos 4 Reloginhos
          h('div', { class: 'countdown-digits-grid', contenteditable: 'false' }, [
            h('div', { class: 'digit-card' }, [h('span', { class: 'num' }, '00'), h('small', 'DIAS')]),
            h('div', { class: 'digit-card' }, [h('span', { class: 'num' }, '00'), h('small', 'HORAS')]),
            h('div', { class: 'digit-card' }, [h('span', { class: 'num' }, '00'), h('small', 'MINUTOS')]),
            h('div', { class: 'digit-card' }, [h('span', { class: 'num' }, '00'), h('small', 'SEGUNDOS')])
          ])

        ])
      ]);
    }
  };

  // 2. EXTENSÃO TIPTAP
  const CountdownExtension = Node.create({
    name: 'countdown',
    group: 'block',
    atom: true,
    draggable: true,

    addAttributes() {
      return {
        title: { default: 'A oferta termina em:' },
        targetDate: { default: '' },
        theme: { default: 'dark' },
        expiredMessage: { default: 'Tempo esgotado!' }
      };
    },

    parseHTML() {
      return [{
        tag: 'div[data-type="countdown"]',
        getAttrs: (element) => ({
          title: element.getAttribute('data-title') || 'A oferta termina em:',
          targetDate: element.getAttribute('data-target-date') || '',
          theme: element.getAttribute('data-theme') || 'dark',
          expiredMessage: element.getAttribute('data-expired-msg') || 'Tempo esgotado!'
        })
      }];
    },

    renderHTML({ HTMLAttributes }) {
      const { title, targetDate, theme, expiredMessage } = HTMLAttributes;

      return [
        'div',
        mergeAttributes(HTMLAttributes, {
          class: `lunar-countdown-box theme-${theme || 'dark'}`,
          'data-type': 'countdown',
          'data-title': title || '',
          'data-target-date': targetDate || '',
          'data-theme': theme || 'dark',
          'data-expired-msg': expiredMessage || 'Tempo esgotado!'
        }),
        ['div', { class: 'countdown-title' }, title || ''],
        ['div', { class: 'countdown-digits-grid' },
          ['div', { class: 'digit-card' }, ['span', { class: 'num c-days' }, '00'], ['small', {}, 'DIAS']],
          ['div', { class: 'digit-card' }, ['span', { class: 'num c-hours' }, '00'], ['small', {}, 'HORAS']],
          ['div', { class: 'digit-card' }, ['span', { class: 'num c-minutes' }, '00'], ['small', {}, 'MINUTOS']],
          ['div', { class: 'digit-card' }, ['span', { class: 'num c-seconds' }, '00'], ['small', {}, 'SEGUNDOS']]
        ],
        ['div', { class: 'countdown-expired-msg', style: 'display: none;' }, expiredMessage || 'Tempo esgotado!']
      ];
    },

    addNodeView() {
      return VueNodeViewRenderer(CountdownComponent);
    }
  });

  // 3. REGISTRA O BLOCO NO MEGA-MENU
  window.LunarEditor.registerBlock({
    name: 'countdown',
    category: 'Estrutura & Layout',
    title: 'Contador Regressivo',
    description: 'Relógio com prazo para promoções e eventos',
    icon: timerIcon,
    extension: CountdownExtension,
    action: (editor) => {
      const nextWeek = new Date();
      nextWeek.setDate(nextWeek.getDate() + 7);

      editor.chain().focus().insertContent({
        type: 'countdown',
        attrs: {
          title: 'A oferta termina em:',
          targetDate: nextWeek.toISOString().slice(0, 16),
          theme: 'dark'
        }
      }).run();
    }
  });

})();
