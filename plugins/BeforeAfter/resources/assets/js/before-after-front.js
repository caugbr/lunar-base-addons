/**
 * Motor de Arraste para o Comparador Before / After no Front-End
 */
document.addEventListener('DOMContentLoaded', () => {
  const containers = document.querySelectorAll('.lunar-ba-container');
  if (!containers.length) return;

  containers.forEach(container => {
    const range = container.querySelector('.lunar-ba-range');
    const beforeWrap = container.querySelector('.lunar-ba-before-wrap');
    const handle = container.querySelector('.lunar-ba-handle');

    // SELECIONA OS BADGES
    const badgeBefore = container.querySelector('.lunar-ba-badge-before');
    const badgeAfter = container.querySelector('.lunar-ba-badge-after');

    if (!range || !beforeWrap) return;

    // GARANTE O ASPECT-RATIO VIA JS
    const imgWidth = parseInt(container.getAttribute('data-img-width'));
    const imgHeight = parseInt(container.getAttribute('data-img-height'));

    if (imgWidth && imgHeight) {
      container.style.aspectRatio = `${imgWidth} / ${imgHeight}`;
    } else {
      container.style.aspectRatio = '16 / 9';
    }

    // FUNÇÃO PARA ATUALIZAR SLIDER E OPACIDADE DOS BADGES
    const updateSlider = (value) => {
      const percent = parseInt(value, 10);

      // Atualiza largura da imagem "Antes"
      beforeWrap.style.width = percent + '%';

      // Atualiza posição da alça
      if (handle) {
        handle.style.left = percent + '%';
      }

      // ATUALIZA OPACIDADE DOS BADGES
      if (badgeBefore && badgeAfter) {
        // Badge "Antes": opacidade = percent (0-100)
        badgeBefore.style.opacity = percent / 100;

        // Badge "Depois": opacidade = inverso (100-percent)
        badgeAfter.style.opacity = (100 - percent) / 100;
      }
    };

    // EVENTO INPUT (arraste em tempo real)
    range.addEventListener('input', (e) => {
      updateSlider(e.target.value);
    });

    // EVENTO CHANGE (finaliza arraste)
    range.addEventListener('change', (e) => {
      updateSlider(e.target.value);
    });

    // INICIALIZA COM 50% E OPACIDADE 50%/50%
    updateSlider(50);
  });
});
