document.addEventListener('click', function(e) {
    const el = e.target.closest('[data-track-event]');
    if (!el) return;

    // Obtém as configurações injetadas pelo backend
    const config = window.TRACKER_CONFIG || {};
    const eventUrl = config.eventUrl || '/tracker/api/event';
    const csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const eventName = el.getAttribute('data-track-event');
    const eventCategory = el.getAttribute('data-track-category') || null;

    const payload = JSON.stringify({
        event_name: eventName,
        event_category: eventCategory,
        path: window.location.pathname,
        _token: csrfToken
    });

    // Envia via sendBeacon (garantido mesmo navegando para outra página)
    if (navigator.sendBeacon) {
        const blob = new Blob([payload], { type: 'application/json' });
        navigator.sendBeacon(eventUrl, blob);
    } else {
        fetch(eventUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: payload,
            keepalive: true
        });
    }
});
