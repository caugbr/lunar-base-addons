(function () {
    if (window.self !== window.top) return;

    const path = window.location.pathname;
    if (path.startsWith('/admin') || path.startsWith('/api')) {
        return;
    }

    let clickBuffer = [];
    let maxScrollPercent = 0;
    let lastSentScrollPercent = 0;

    const hasMouse = window.matchMedia('(hover: hover)').matches;
    const allowedSelectors = window.__heatmapTrackedSelectors
        || 'a, button, input, select, textarea, img, label, summary, [role="button"]';

    // =========================================================================
    // 1. RASTREAMENTO DE ROLAGEM / PROFUNDIDADE (SCROLL MAP)
    // =========================================================================
    function updateScrollDepth() {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const windowHeight = window.innerHeight;
        const docHeight = Math.max(
            document.body.scrollHeight,
            document.documentElement.scrollHeight,
            windowHeight
        );

        const totalScrollable = docHeight - windowHeight;
        if (totalScrollable <= 0) {
            maxScrollPercent = 100;
            return;
        }

        // Calcula a porcentagem visível do rodapé da tela
        const currentPercent = Math.min(100, Math.round(((scrollTop + windowHeight) / docHeight) * 100));
        if (currentPercent > maxScrollPercent) {
            maxScrollPercent = currentPercent;
        }
    }

    // Ouvinte passivo de scroll
    window.addEventListener('scroll', updateScrollDepth, { passive: true });
    updateScrollDepth(); // Medição inicial da área visível no carregamento

    // =========================================================================
    // 2. GERADOR DE SELETOR SEMÂNTICO
    // =========================================================================
    function getDomSelector(el) {
        if (!el || el.nodeType !== Node.ELEMENT_NODE) return null;

        if (el.id && !el.id.match(/\d{4,}/)) {
            return `#${CSS.escape(el.id)}`;
        }

        if (el.tagName === 'A' && el.getAttribute('href')) {
            const href = el.getAttribute('href').trim();
            if (href && !href.startsWith('javascript:') && href.length < 100) {
                return `a[href="${CSS.escape(href)}"]`;
            }
        }

        if (['INPUT', 'SELECT', 'TEXTAREA'].includes(el.tagName) && el.getAttribute('name')) {
            return `${el.tagName.toLowerCase()}[name="${CSS.escape(el.getAttribute('name'))}"]`;
        }

        let pathParts = [];
        let current = el;

        while (current && current.nodeType === Node.ELEMENT_NODE && current !== document.body && current !== document.documentElement) {
            let tag = current.tagName.toLowerCase();

            if (current.id && !current.id.match(/\d{4,}/)) {
                pathParts.unshift(`#${CSS.escape(current.id)}`);
                break;
            }

            let sibling = current;
            let nth = 1;
            while (sibling = sibling.previousElementSibling) {
                if (sibling.tagName === current.tagName) nth++;
            }

            let part = tag;
            if (nth > 1) part += `:nth-of-type(${nth})`;

            pathParts.unshift(part);
            current = current.parentElement;

            if (pathParts.length >= 4) break;
        }

        return pathParts.join(' > ');
    }

    // =========================================================================
    // 3. CLIQUES (CLICK MAP)
    // =========================================================================
    document.addEventListener('click', function (e) {
        if (e.button !== 0) return;

        const target = e.target.closest(allowedSelectors);
        if (!target) return;

        const selector = getDomSelector(target);
        if (!selector) return;

        clickBuffer.push({
            type: 'click',
            selector: selector
        });

        if (clickBuffer.length >= 10) flush();
    }, { passive: true });

    // =========================================================================
    // 4. ATENÇÃO / HOVER (MOVE MAP)
    // =========================================================================
    if (hasMouse) {
        let hoverTimer = null;
        let lastHoveredElement = null;
        let recordedHovers = new Set();

        document.addEventListener('mouseover', function (e) {
            const target = e.target.closest(allowedSelectors);
            if (!target || target === lastHoveredElement) return;

            clearTimeout(hoverTimer);
            lastHoveredElement = target;

            const hoverDelay = window.__heatmapHoverDelay || 1200;

            hoverTimer = setTimeout(() => {
                const selector = getDomSelector(target);
                if (selector && !recordedHovers.has(selector)) {
                    recordedHovers.add(selector);
                    clickBuffer.push({
                        type: 'hover',
                        selector: selector
                    });
                    if (clickBuffer.length >= 10) flush();
                }
            }, hoverDelay);
        }, { passive: true });

        document.addEventListener('mouseout', function (e) {
            if (e.target === lastHoveredElement) {
                clearTimeout(hoverTimer);
                lastHoveredElement = null;
            }
        }, { passive: true });
    }

    // =========================================================================
    // 5. DESPACHO EM LOTE (BEACON)
    // =========================================================================
    function flush() {
        const hasClicks = clickBuffer.length > 0;
        const hasNewScroll = maxScrollPercent > lastSentScrollPercent;

        if (!hasClicks && !hasNewScroll) return;

        const payload = JSON.stringify({
            path: path,
            interactions: clickBuffer,
            scroll_percent: hasNewScroll ? maxScrollPercent : null
        });

        lastSentScrollPercent = maxScrollPercent;
        clickBuffer = [];

        const endpoint = '/api/heatmap/track';

        if (navigator.sendBeacon) {
            navigator.sendBeacon(endpoint, new Blob([payload], { type: 'application/json' }));
        } else {
            fetch(endpoint, {
                method: 'POST',
                body: payload,
                headers: { 'Content-Type': 'application/json' },
                keepalive: true
            }).catch(() => {});
        }
    }

    setInterval(flush, 15000);
    window.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') flush();
    });
})();
