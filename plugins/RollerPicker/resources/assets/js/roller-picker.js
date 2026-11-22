(function () {
    'use strict';

    const MONTH_NAMES = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    const HOURS_LIST = Array.from({ length: 24 }, (_, i) => String(i).padStart(2, '0'));
    const MINUTES_LIST = Array.from({ length: 60 }, (_, i) => String(i).padStart(2, '0'));

    class TouchWheel {
        constructor(wrapper, track, itemsList, initialIndex, onChange) {
            this.wrapper = wrapper;
            this.track = track;
            this.itemsList = itemsList;
            this.onChange = onChange;
            this.items = [];

            this.ITEM_HEIGHT = 56;
            this.RADIUS = 110;

            const safeIndex = Math.max(0, Math.min(itemsList.length - 1, initialIndex));
            this.currentY = -safeIndex * this.ITEM_HEIGHT;
            this.targetY = this.currentY;
            this.lastVibratedIndex = safeIndex;

            this.isDragging = false;
            this.startY = 0;
            this.lastY = 0;
            this.velocity = 0;
            this.lastTime = 0;
            this.animFrame = null;

            this.boundOnStart = this.onStart.bind(this);
            this.boundOnMove = this.onMove.bind(this);
            this.boundOnEnd = this.onEnd.bind(this);

            this.initDOM();
            this.bindEvents();
            this.render();
        }

        initDOM() {
            this.track.innerHTML = '';
            this.items = [];
            for (let i = 0; i < this.itemsList.length; i++) {
                const div = document.createElement('div');
                div.className = 'wheel-item';
                div.textContent = this.itemsList[i];
                this.track.appendChild(div);
                this.items.push(div);
            }
        }

        setItems(newItemsList) {
            this.itemsList = newItemsList;
            this.initDOM();

            let currentIndex = Math.round(-this.targetY / this.ITEM_HEIGHT);
            let clamped = false;
            if (currentIndex >= this.itemsList.length) {
                currentIndex = this.itemsList.length - 1;
                this.targetY = -currentIndex * this.ITEM_HEIGHT;
                clamped = true;
            }
            if (currentIndex < 0) {
                currentIndex = 0;
                this.targetY = 0;
                clamped = true;
            }

            if (clamped) {
                this.onChange(this.itemsList[currentIndex], currentIndex);
                this.animateToTarget();
            } else {
                this.render();
            }
        }

        bindEvents() {
            this.wrapper.addEventListener('pointerdown', this.boundOnStart);
            this.wrapper.addEventListener('pointermove', this.boundOnMove);
            this.wrapper.addEventListener('pointerup', this.boundOnEnd);
            this.wrapper.addEventListener('pointercancel', this.boundOnEnd);
        }

        destroy() {
            cancelAnimationFrame(this.animFrame);
            this.wrapper.removeEventListener('pointerdown', this.boundOnStart);
            this.wrapper.removeEventListener('pointermove', this.boundOnMove);
            this.wrapper.removeEventListener('pointerup', this.boundOnEnd);
            this.wrapper.removeEventListener('pointercancel', this.boundOnEnd);
        }

        onStart(e) {
            this.isDragging = true;
            this.startY = e.clientY;
            this.lastY = e.clientY;
            this.lastTime = performance.now();
            this.velocity = 0;
            cancelAnimationFrame(this.animFrame);

            try {
                this.wrapper.setPointerCapture(e.pointerId);
            } catch (err) {}
        }

        onMove(e) {
            if (!this.isDragging) return;
            const now = performance.now();
            const deltaY = e.clientY - this.lastY;
            const deltaTime = Math.max(now - this.lastTime, 1);

            this.currentY += deltaY;
            this.velocity = deltaY / deltaTime;
            this.lastY = e.clientY;
            this.lastTime = now;
            this.render();
        }

        onEnd(e) {
            if (!this.isDragging) return;
            this.isDragging = false;

            try {
                if (this.wrapper.hasPointerCapture(e.pointerId)) {
                    this.wrapper.releasePointerCapture(e.pointerId);
                }
            } catch (err) {}

            const momentum = this.velocity * 160;
            this.targetY = this.currentY + momentum;

            let nearestIndex = Math.round(-this.targetY / this.ITEM_HEIGHT);
            nearestIndex = Math.max(0, Math.min(this.itemsList.length - 1, nearestIndex));
            this.targetY = -nearestIndex * this.ITEM_HEIGHT;

            // Atualiza já no snap caso o usuário clique OK antes da desaceleração terminar
            this.onChange(this.itemsList[nearestIndex], nearestIndex);
            this.animateToTarget();
        }

        animateToTarget() {
            const animate = () => {
                const diff = this.targetY - this.currentY;
                if (Math.abs(diff) > 0.5) {
                    this.currentY += diff * 0.14;
                    this.render();
                    this.animFrame = requestAnimationFrame(animate);
                } else {
                    this.currentY = this.targetY;
                    this.render();
                    const finalIndex = Math.round(-this.currentY / this.ITEM_HEIGHT);
                    this.onChange(this.itemsList[finalIndex], finalIndex);
                }
            };
            cancelAnimationFrame(this.animFrame);
            this.animFrame = requestAnimationFrame(animate);
        }

        render() {
            const centerIndex = -this.currentY / this.ITEM_HEIGHT;
            const roundedIndex = Math.round(centerIndex);

            if (roundedIndex !== this.lastVibratedIndex && roundedIndex >= 0 && roundedIndex < this.itemsList.length) {
                this.lastVibratedIndex = roundedIndex;
                if ('vibrate' in navigator) {
                    try { navigator.vibrate(6); } catch (err) {}
                }
            }

            for (let index = 0; index < this.items.length; index++) {
                const item = this.items[index];
                const distance = index - centerIndex;
                if (Math.abs(distance) <= 2.8) {
                    item.style.display = 'flex';
                    const angle = distance * 28;
                    item.style.transform = `rotateX(${-angle}deg) translateZ(${this.RADIUS}px)`;
                    item.style.opacity = Math.max(0, 1 - Math.abs(distance) * 0.35);
                    item.style.color = Math.abs(distance) < 0.5 ? '#ffffff' : '#888899';
                } else {
                    item.style.display = 'none';
                }
            }
        }
    }

    function getDaysInMonth(yearStr, monthIndex) {
        return new Date(parseInt(yearStr, 10), monthIndex + 1, 0).getDate();
    }

    function getDaysArray(maxDays) {
        return Array.from({ length: maxDays }, (_, i) => String(i + 1).padStart(2, '0'));
    }

    // Modal singleton compartilhado por todos os campos da página
    const ModalManager = {
        el: null,
        card: null,
        titleEl: null,
        refs: {},
        wheels: [],
        daysWheel: null,
        activeField: null,
        activeInput: null,
        state: {
            type: 'date',
            minYear: 1930,
            maxYear: new Date().getFullYear(),
            day: '15',
            monthIdx: 5,
            year: '1995',
            hour: '12',
            minute: '00'
        },

        ensureDOM() {
            if (this.el) return;

            const modal = document.createElement('div');
            modal.className = 'roller-universal-modal';
            modal.hidden = true;
            modal.innerHTML = `
                <div class="picker-card" role="dialog" aria-modal="true">
                    <div class="card-header">
                        <button type="button" class="btn-cancel">Cancelar</button>
                        <span class="card-title">Selecionar</span>
                        <button type="button" class="btn-confirm">OK</button>
                    </div>
                    <div class="picker-body">
                        <div class="highlight-bar"></div>
                        <div class="wheel-wrapper wheel-day"><div class="wheel-track"></div></div>
                        <div class="wheel-wrapper wheel-month"><div class="wheel-track"></div></div>
                        <div class="wheel-wrapper wheel-year"><div class="wheel-track"></div></div>
                        <div class="datetime-separator"></div>
                        <div class="wheel-wrapper wheel-hour"><div class="wheel-track"></div></div>
                        <div class="divider">:</div>
                        <div class="wheel-wrapper wheel-minute"><div class="wheel-track"></div></div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            this.el = modal;
            this.card = modal.querySelector('.picker-card');
            this.titleEl = modal.querySelector('.card-title');
            this.refs = {
                daysWrapper: modal.querySelector('.wheel-day'),
                daysTrack: modal.querySelector('.wheel-day .wheel-track'),
                monthsWrapper: modal.querySelector('.wheel-month'),
                monthsTrack: modal.querySelector('.wheel-month .wheel-track'),
                yearsWrapper: modal.querySelector('.wheel-year'),
                yearsTrack: modal.querySelector('.wheel-year .wheel-track'),
                dtSep: modal.querySelector('.datetime-separator'),
                hoursWrapper: modal.querySelector('.wheel-hour'),
                hoursTrack: modal.querySelector('.wheel-hour .wheel-track'),
                timeDivider: modal.querySelector('.divider'),
                minutesWrapper: modal.querySelector('.wheel-minute'),
                minutesTrack: modal.querySelector('.wheel-minute .wheel-track')
            };

            modal.addEventListener('click', (e) => {
                if (e.target === modal) this.close();
            });
            modal.querySelector('.btn-cancel').addEventListener('click', () => this.close());
            modal.querySelector('.btn-confirm').addEventListener('click', () => this.confirm());

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.el && !this.el.hidden) {
                    this.close();
                }
            });
        },

        parseInitialValues(input, type, minYear, maxYear) {
            const clampedFallbackYear = Math.max(minYear, Math.min(maxYear, 1995));
            this.state = {
                type,
                minYear,
                maxYear,
                day: '15',
                monthIdx: 5,
                year: String(clampedFallbackYear),
                hour: '12',
                minute: '00'
            };

            const val = (input.value || '').trim();
            if (!val) return;

            if (type === 'time') {
                if (val.includes(':')) {
                    const [h, m] = val.split(':');
                    this.state.hour = String(Math.max(0, Math.min(23, parseInt(h, 10) || 0))).padStart(2, '0');
                    this.state.minute = String(Math.max(0, Math.min(59, parseInt(m, 10) || 0))).padStart(2, '0');
                }
            } else if (type === 'date') {
                if (val.includes('-')) {
                    const [y, m, d] = val.split('-');
                    const yNum = Math.max(minYear, Math.min(maxYear, parseInt(y, 10) || clampedFallbackYear));
                    this.state.year = String(yNum);
                    this.state.monthIdx = Math.max(0, Math.min(11, (parseInt(m, 10) || 1) - 1));
                    this.state.day = String(Math.max(1, Math.min(31, parseInt(d, 10) || 1))).padStart(2, '0');
                }
            } else if (type === 'datetime-local') {
                const [datePart, timePart] = val.split(/[T ]/);
                if (datePart && datePart.includes('-')) {
                    const [y, m, d] = datePart.split('-');
                    const yNum = Math.max(minYear, Math.min(maxYear, parseInt(y, 10) || clampedFallbackYear));
                    this.state.year = String(yNum);
                    this.state.monthIdx = Math.max(0, Math.min(11, (parseInt(m, 10) || 1) - 1));
                    this.state.day = String(Math.max(1, Math.min(31, parseInt(d, 10) || 1))).padStart(2, '0');
                }
                if (timePart && timePart.includes(':')) {
                    const [h, m] = timePart.split(':');
                    this.state.hour = String(Math.max(0, Math.min(23, parseInt(h, 10) || 0))).padStart(2, '0');
                    this.state.minute = String(Math.max(0, Math.min(59, parseInt(m, 10) || 0))).padStart(2, '0');
                }
            }
        },

        destroyWheels() {
            this.wheels.forEach((w) => w.destroy());
            this.wheels = [];
            this.daysWheel = null;
        },

        updateDaysList() {
            if (!this.daysWheel) return;
            const maxDays = getDaysInMonth(this.state.year, this.state.monthIdx);
            this.daysWheel.setItems(getDaysArray(maxDays));
        },

        open(field, input) {
            this.ensureDOM();
            this.destroyWheels();

            const type = field.dataset.type || 'date';
            const minYear = parseInt(field.dataset.minYear || '1930', 10);
            const maxYear = parseInt(field.dataset.maxYear || String(new Date().getFullYear()), 10);
            const title = field.dataset.title || 'Selecionar';

            this.activeField = field;
            this.activeInput = input;
            this.parseInitialValues(input, type, minYear, maxYear);

            const hasDate = type === 'date' || type === 'datetime-local';
            const hasTime = type === 'time' || type === 'datetime-local';

            this.titleEl.textContent = title;
            this.card.classList.toggle('is-datetime', type === 'datetime-local');

            this.refs.daysWrapper.hidden = !hasDate;
            this.refs.monthsWrapper.hidden = !hasDate;
            this.refs.yearsWrapper.hidden = !hasDate;
            this.refs.dtSep.hidden = type !== 'datetime-local';
            this.refs.hoursWrapper.hidden = !hasTime;
            this.refs.timeDivider.hidden = !hasTime;
            this.refs.minutesWrapper.hidden = !hasTime;

            this.el.hidden = false;
            document.body.classList.add('roller-modal-open');

            if (hasDate) {
                const yearsList = [];
                for (let y = minYear; y <= maxYear; y++) {
                    yearsList.push(String(y));
                }

                const maxDays = getDaysInMonth(this.state.year, this.state.monthIdx);
                const initialDaysList = getDaysArray(maxDays);
                const initialDayIdx = Math.max(0, Math.min(initialDaysList.length - 1, parseInt(this.state.day, 10) - 1));
                this.state.day = initialDaysList[initialDayIdx];

                this.daysWheel = new TouchWheel(
                    this.refs.daysWrapper,
                    this.refs.daysTrack,
                    initialDaysList,
                    initialDayIdx,
                    (val) => { this.state.day = val; }
                );
                this.wheels.push(this.daysWheel);

                this.wheels.push(new TouchWheel(
                    this.refs.monthsWrapper,
                    this.refs.monthsTrack,
                    MONTH_NAMES,
                    this.state.monthIdx,
                    (_, idx) => {
                        this.state.monthIdx = idx;
                        this.updateDaysList();
                    }
                ));

                const initialYearIdx = Math.max(0, yearsList.indexOf(this.state.year));
                this.wheels.push(new TouchWheel(
                    this.refs.yearsWrapper,
                    this.refs.yearsTrack,
                    yearsList,
                    initialYearIdx,
                    (val) => {
                        this.state.year = val;
                        this.updateDaysList();
                    }
                ));
            }

            if (hasTime) {
                this.wheels.push(new TouchWheel(
                    this.refs.hoursWrapper,
                    this.refs.hoursTrack,
                    HOURS_LIST,
                    parseInt(this.state.hour, 10),
                    (val) => { this.state.hour = val; }
                ));

                this.wheels.push(new TouchWheel(
                    this.refs.minutesWrapper,
                    this.refs.minutesTrack,
                    MINUTES_LIST,
                    parseInt(this.state.minute, 10),
                    (val) => { this.state.minute = val; }
                ));
            }
        },

        confirm() {
            if (!this.activeInput) {
                this.close();
                return;
            }

            const maxDays = getDaysInMonth(this.state.year, this.state.monthIdx);
            const safeDay = Math.min(parseInt(this.state.day, 10) || 1, maxDays);
            const monthStr = String(this.state.monthIdx + 1).padStart(2, '0');
            const dayStr = String(safeDay).padStart(2, '0');

            let result = '';
            if (this.state.type === 'date') {
                result = `${this.state.year}-${monthStr}-${dayStr}`;
            } else if (this.state.type === 'time') {
                result = `${this.state.hour}:${this.state.minute}`;
            } else if (this.state.type === 'datetime-local') {
                result = `${this.state.year}-${monthStr}-${dayStr}T${this.state.hour}:${this.state.minute}`;
            }

            this.activeInput.value = result;
            this.activeInput.dispatchEvent(new Event('input', { bubbles: true }));
            this.activeInput.dispatchEvent(new Event('change', { bubbles: true }));

            this.close();
        },

        close() {
            if (!this.el) return;
            this.el.hidden = true;
            document.body.classList.remove('roller-modal-open');
            this.destroyWheels();
            this.activeField = null;
            this.activeInput = null;
        }
    };

    const mediaQueryCache = new Map();

    function getMediaQuery(breakpoint) {
        const key = String(breakpoint);
        if (!mediaQueryCache.has(key)) {
            mediaQueryCache.set(key, window.matchMedia(`(max-width: ${breakpoint}px)`));
        }
        return mediaQueryCache.get(key);
    }

    function initField(field) {
        if (field.dataset.rollerInit === '1') return;
        field.dataset.rollerInit = '1';

        const input = field.querySelector('.roller-native-input');
        const trigger = field.querySelector('.input-trigger');
        if (!input || !trigger) return;

        const breakpoint = parseInt(field.dataset.breakpoint || '768', 10);
        const mql = getMediaQuery(breakpoint);

        const syncBreakpoint = () => {
            field.classList.toggle('is-mobile', mql.matches);
        };

        syncBreakpoint();
        if (typeof mql.addEventListener === 'function') {
            mql.addEventListener('change', syncBreakpoint);
        } else if (typeof mql.addListener === 'function') {
            mql.addListener(syncBreakpoint);
        }

        const tryOpen = (e) => {
            if (!mql.matches || input.disabled || input.readOnly) return;
            if (e) e.preventDefault();
            input.blur();
            ModalManager.open(field, input);
        };

        trigger.addEventListener('click', tryOpen);

        // Caso o foco chegue via <label for="..."> ou navegação por Tab no mobile
        input.addEventListener('focus', () => {
            if (mql.matches && !input.disabled && !input.readOnly) {
                input.blur();
                ModalManager.open(field, input);
            }
        });
    }

    function initAll(root = document) {
        root.querySelectorAll('[data-roller-picker]').forEach(initField);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => initAll());
    } else {
        initAll();
    }

    window.RollerPicker = { init: initAll };
})();
