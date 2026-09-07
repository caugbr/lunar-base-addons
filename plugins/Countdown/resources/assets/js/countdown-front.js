/**
 * Motor do Relógio Regressivo para o Front-End
 */
document.addEventListener('DOMContentLoaded', () => {
  const countdowns = document.querySelectorAll('.lunar-countdown-box[data-target-date]');
  if (!countdowns.length) return;

  countdowns.forEach(box => {
    const rawDate = box.getAttribute('data-target-date');
    if (!rawDate) return;

    const targetTime = new Date(rawDate).getTime();
    if (isNaN(targetTime)) return;

    const elDays = box.querySelector('.c-days');
    const elHours = box.querySelector('.c-hours');
    const elMinutes = box.querySelector('.c-minutes');
    const elSeconds = box.querySelector('.c-seconds');
    const digitsGrid = box.querySelector('.countdown-digits-grid');
    const expiredMsg = box.querySelector('.countdown-expired-msg');

    const pad = (n) => String(n).padStart(2, '0');

    const updateTimer = () => {
      const now = new Date().getTime();
      const distance = targetTime - now;

      if (distance <= 0) {
        if (digitsGrid) digitsGrid.style.display = 'none';
        if (expiredMsg) expiredMsg.style.display = 'block';
        return;
      }

      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance / (1000 * 60 * 60)) % 24);
      const minutes = Math.floor((distance / 1000 / 60) % 60);
      const seconds = Math.floor((distance / 1000) % 60);

      if (elDays) elDays.innerText = pad(days);
      if (elHours) elHours.innerText = pad(hours);
      if (elMinutes) elMinutes.innerText = pad(minutes);
      if (elSeconds) elSeconds.innerText = pad(seconds);
    };

    updateTimer();
    setInterval(updateTimer, 1000);
  });
});
