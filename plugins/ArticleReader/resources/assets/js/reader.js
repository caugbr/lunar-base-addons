document.addEventListener('DOMContentLoaded', () => {
  const players = document.querySelectorAll('.lunar-article-reader');
  if (!players.length || !('speechSynthesis' in window)) {
    players.forEach(p => p.style.display = 'none');
    return;
  }

  players.forEach(container => {
    // Lê o texto direto da div oculta fornecida pelo Blade
    const textElement = container.querySelector('.lunar-reader-source-text');
    const textToRead = textElement ? textElement.textContent.trim() : '';
    if (!textToRead) return;

    const btnPlay = container.querySelector('.lunar-reader-btn-play');
    const iconPlay = container.querySelector('.icon-play');
    const iconPause = container.querySelector('.icon-pause');
    const progressBar = container.querySelector('.lunar-reader-progress-bar');
    const btnSpeed = container.querySelector('.lunar-reader-speed-btn');

    let isPlaying = false;
    let currentSpeed = 1.0;
    const speeds = [1.0, 1.25, 1.5, 2.0];
    let utterance = null;

    const setupUtterance = () => {
      utterance = new SpeechSynthesisUtterance(textToRead);
      utterance.lang = 'pt-BR';
      utterance.rate = currentSpeed;

      utterance.onboundary = (e) => {
        if (e.charIndex) {
          const pct = Math.min(100, Math.round((e.charIndex / textToRead.length) * 100));
          if (progressBar) progressBar.style.width = pct + '%';
        }
      };

      utterance.onend = () => {
        isPlaying = false;
        if (iconPlay) iconPlay.style.display = 'block';
        if (iconPause) iconPause.style.display = 'none';
        if (progressBar) progressBar.style.width = '100%';
      };
    };

    btnPlay.addEventListener('click', () => {
      if (!isPlaying) {
        if (window.speechSynthesis.paused) {
          window.speechSynthesis.resume();
        } else {
          window.speechSynthesis.cancel();
          setupUtterance();
          window.speechSynthesis.speak(utterance);
        }
        isPlaying = true;
        if (iconPlay) iconPlay.style.display = 'none';
        if (iconPause) iconPause.style.display = 'block';
      } else {
        window.speechSynthesis.pause();
        isPlaying = false;
        if (iconPlay) iconPlay.style.display = 'block';
        if (iconPause) iconPause.style.display = 'none';
      }
    });

    btnSpeed.addEventListener('click', () => {
      const idx = (speeds.indexOf(currentSpeed) + 1) % speeds.length;
      currentSpeed = speeds[idx];
      btnSpeed.innerText = currentSpeed + 'x';

      if (isPlaying) {
        window.speechSynthesis.cancel();
        setupUtterance();
        window.speechSynthesis.speak(utterance);
      }
    });
  });
});
