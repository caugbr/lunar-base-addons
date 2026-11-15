@props([
    'name' => 'city',
    'id' => null,
    'value' => '',
    'placeholder' => 'Digite o nome da cidade...',
    'country' => null,
    'limit' => 12,
    'useAscii' => true,
    'latTarget' => null,
    'lngTarget' => null,
    'timezoneTarget' => null,
    'cityAsciiTarget' => null,
    'class' => 'form-input',
    'required' => false,
])

@php
    $inputId = $id ?: 'city_autocomplete_' . uniqid();
@endphp

<div class="cities-autocomplete-wrap" data-cities-autocomplete>
    <input
        type="text"
        name="{{ $name }}"
        id="{{ $inputId }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        class="{{ $class }}"
        autocomplete="off"
        data-endpoint="{{ route('cities.search') }}"
        data-country="{{ $country ?? '' }}"
        data-limit="{{ (int) $limit }}"
        data-use-ascii="{{ $useAscii ? '1' : '0' }}"
        data-lat-target="{{ $latTarget ?? '' }}"
        data-lng-target="{{ $lngTarget ?? '' }}"
        data-timezone-target="{{ $timezoneTarget ?? '' }}"
        data-city-ascii-target="{{ $cityAsciiTarget ?? '' }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes }}
    >
    <div class="cities-autocomplete-dropdown" hidden></div>
</div>

@once
@push('styles')
<style>
    .cities-autocomplete-wrap {
        position: relative;
        width: 100%;
    }

    .cities-autocomplete-dropdown {
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        z-index: 999;
        max-height: 260px;
        overflow-y: auto;
        background: var(--color-bg-dark, #12152b);
        border: 1px solid var(--color-border, rgba(200, 182, 255, 0.2));
        border-radius: 6px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
    }

    .cities-autocomplete-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid var(--color-border, rgba(200, 182, 255, 0.08));
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        font-size: 0.875rem;
        color: var(--color-text, #e8e6f0);
    }

    .cities-autocomplete-item:last-child {
        border-bottom: none;
    }

    .cities-autocomplete-item:hover,
    .cities-autocomplete-item.is-active {
        background: rgba(56, 189, 248, 0.14);
    }

    .cities-autocomplete-main {
        font-weight: 500;
    }

    .cities-autocomplete-meta {
        font-size: 0.75rem;
        color: var(--color-text-muted, #94a3b8);
        white-space: nowrap;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text ?? '';
        return div.innerHTML;
    }

    function setTargetValue(selector, val) {
        if (!selector || val === null || val === undefined) return;
        const target = document.querySelector(selector);
        if (!target) return;
        target.value = val;
        target.dispatchEvent(new Event('input', { bubbles: true }));
        target.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function initWrapper(wrap) {
        if (wrap.dataset.initialized === '1') return;
        wrap.dataset.initialized = '1';

        const input = wrap.querySelector('input');
        const dropdown = wrap.querySelector('.cities-autocomplete-dropdown');
        if (!input || !dropdown) return;

        let debounceTimer = null;
        let abortController = null;
        let items = [];
        let activeIndex = -1;

        function closeDropdown() {
            dropdown.hidden = true;
            dropdown.innerHTML = '';
            items = [];
            activeIndex = -1;
        }

        function highlightActive() {
            const nodes = dropdown.querySelectorAll('.cities-autocomplete-item');
            nodes.forEach((node, idx) => {
                node.classList.toggle('is-active', idx === activeIndex);
            });
            if (activeIndex >= 0 && nodes[activeIndex]) {
                nodes[activeIndex].scrollIntoView({ block: 'nearest' });
            }
        }

        function selectItem(item) {
            if (!item) return;

            const useAscii = input.dataset.useAscii === '1';
            const adminSuffix = item.admin_name ? `, ${item.admin_name}` : '';
            const baseCity = useAscii ? item.city_ascii : item.city;

            input.value = `${baseCity}${adminSuffix} - ${item.country}`;

            setTargetValue(input.dataset.cityAsciiTarget, item.city_ascii);
            setTargetValue(input.dataset.latTarget, item.lat);
            setTargetValue(input.dataset.lngTarget, item.lng);
            setTargetValue(input.dataset.timezoneTarget, item.timezone);

            input.dispatchEvent(new CustomEvent('city:selected', {
                detail: item,
                bubbles: true,
            }));

            closeDropdown();
        }

        function renderDropdown(data) {
            items = Array.isArray(data) ? data : [];
            activeIndex = -1;

            if (items.length === 0) {
                closeDropdown();
                return;
            }

            dropdown.innerHTML = items.map((item, idx) => {
                const tz = item.timezone ? escapeHtml(item.timezone) : '';
                return `
                    <div class="cities-autocomplete-item" data-index="${idx}">
                        <span class="cities-autocomplete-main">${escapeHtml(item.label)}</span>
                        <span class="cities-autocomplete-meta">${tz}</span>
                    </div>
                `;
            }).join('');

            dropdown.hidden = false;

            dropdown.querySelectorAll('.cities-autocomplete-item').forEach((el) => {
                el.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    const idx = Number(el.dataset.index);
                    selectItem(items[idx]);
                });
            });
        }

        async function fetchCities(query) {
            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();

            const url = new URL(input.dataset.endpoint, window.location.origin);
            url.searchParams.set('q', query);
            url.searchParams.set('limit', input.dataset.limit || '12');
            if (input.dataset.country) {
                url.searchParams.set('country', input.dataset.country);
            }

            try {
                const res = await fetch(url.toString(), {
                    headers: { 'Accept': 'application/json' },
                    signal: abortController.signal,
                });
                if (!res.ok) return;
                const json = await res.json();
                renderDropdown(json.data || []);
            } catch (err) {
                if (err.name !== 'AbortError') {
                    closeDropdown();
                }
            }
        }

        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const val = input.value.trim();

            if (val.length < 2) {
                closeDropdown();
                return;
            }

            debounceTimer = setTimeout(() => fetchCities(val), 250);
        });

        input.addEventListener('keydown', (e) => {
            if (dropdown.hidden || items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = (activeIndex + 1) % items.length;
                highlightActive();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = (activeIndex - 1 + items.length) % items.length;
                highlightActive();
            } else if (e.key === 'Enter' && activeIndex >= 0) {
                e.preventDefault();
                selectItem(items[activeIndex]);
            } else if (e.key === 'Escape') {
                closeDropdown();
            }
        });

        input.addEventListener('blur', () => {
            setTimeout(closeDropdown, 150);
        });
    }

    function initAll() {
        document.querySelectorAll('[data-cities-autocomplete]').forEach(initWrapper);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
</script>
@endpush
@endonce
