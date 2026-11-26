/* Панель не меняет размеры карты. Меняется только видимая часть панели:
   карта, карточка выбранного клуба или список на всю доступную высоту. */
export function createClubDrawer(root, onChange = () => {}) {
    const panel = root.querySelector('[data-club-drawer]');
    const handle = root.querySelector('[data-drawer-handle]');
    const list = root.querySelector('[data-drawer-list]');
    const selection = root.querySelector('[data-mobile-selection]');
    const mapButton = root.querySelector('[data-drawer-map]');
    const hint = root.querySelector('[data-drawer-action]');
    const filters = root.querySelector('.rp-sports');
    const frame = root.querySelector('.rp-map-frame');
    const phone = window.matchMedia('(max-width: 720px)');
    if (!panel || !handle || !list || !selection || !mapButton) return null;
    let view = 'map', hasSelection = false, available = true, drag = null;
    let ignoreClickUntil = 0, refreshFrame = 0;

    function dimensions() {
        const filter = Math.ceil(filters?.getBoundingClientRect().height || 0);
        const maximum = Math.max(0, root.clientHeight - filter);
        const header = Math.min(maximum, Math.ceil(handle.parentElement.getBoundingClientRect().height));
        const card = Math.ceil((selection.querySelector('[data-selected-card]')?.scrollHeight || 0) + 20);
        const peek = Math.min(maximum, Math.max(header, Math.min(maximum * .72, header + card)));
        return { filter, maximum, header, peek };
    }
    function visibleHeight(state, size = dimensions()) {
        return state === 'list' ? size.maximum : state === 'peek' ? size.peek : size.header;
    }
    function sync() {
        const mobile = phone.matches;
        if (mobile) root.scrollTop = 0;
        root.dataset.drawerState = view;
        list.hidden = available && (!mobile || view !== 'list');
        selection.hidden = !mobile || view !== 'peek' || !hasSelection;
        if (mobile && view !== 'list' && list.contains(document.activeElement)) handle.focus();
        const size = dimensions();
        root.style.setProperty('--rp-filter-height', `${size.filter}px`);
        root.style.setProperty('--rp-drawer-visible', `${visibleHeight(view, size)}px`);
        root.style.setProperty('--rp-drawer-maximum', `${size.maximum}px`);
        handle.setAttribute('aria-expanded', String(view === 'list'));
        handle.setAttribute('aria-label', view === 'list' ? 'Volver al mapa' : 'Abrir la lista de clubes');
        if (hint) hint.textContent = view === 'list' ? 'Volver al mapa' : 'Ver lista';
        mapButton.hidden = view !== 'list';
        mapButton.disabled = !available;
        if (frame) {
            const hidden = !available || (mobile && view === 'list');
            frame.inert = hidden;
            frame.setAttribute('aria-hidden', String(hidden));
        }
        const map = root.querySelector('#rp-club-map');
        map?.style.setProperty('--rp-sheet-overlap', `${mobile && view !== 'list' ? visibleHeight(view, size) : 0}px`);
        onChange(view);
    }
    function setView(next) {
        if (!['map', 'peek', 'list'].includes(next)) return;
        if (!available) next = 'list';
        if (next === 'peek' && !hasSelection) next = 'map';
        view = next;
        panel.removeAttribute('data-dragging');
        sync();
    }
    handle.addEventListener('click', () => {
        if (performance.now() < ignoreClickUntil) return;
        setView(view === 'list' ? (hasSelection ? 'peek' : 'map') : 'list');
    });
    handle.addEventListener('keydown', (event) => {
        if (!['ArrowUp', 'ArrowDown', 'Home', 'End', 'Escape'].includes(event.key)) return;
        event.preventDefault();
        setView(['ArrowUp', 'End'].includes(event.key) ? 'list' : 'map');
    });
    mapButton.addEventListener('click', () => setView('map'));
    root.querySelector('[data-selection-close]')?.addEventListener('click', () => setView('map'));
    handle.addEventListener('pointerdown', (event) => {
        if (!phone.matches || !available || event.button !== 0) return;
        drag = { id: event.pointerId, y: event.clientY, height: visibleHeight(view), moved: false };
        handle.setPointerCapture(event.pointerId);
    });
    handle.addEventListener('pointermove', (event) => {
        if (!drag || drag.id !== event.pointerId) return;
        const delta = drag.y - event.clientY;
        if (!drag.moved && Math.abs(delta) < 8) return;
        drag.moved = true;
        if (event.cancelable) event.preventDefault();
        const size = dimensions();
        panel.dataset.dragging = 'true';
        root.style.setProperty('--rp-drawer-visible', `${Math.max(size.header, Math.min(size.maximum, drag.height + delta))}px`);
    });
    handle.addEventListener('pointerup', (event) => {
        if (!drag || drag.id !== event.pointerId) return;
        const { y, moved } = drag;
        drag = null;
        if (moved) {
            ignoreClickUntil = performance.now() + 400;
            const delta = y - event.clientY;
            setView(Math.abs(delta) < 30 ? view : delta > 0 ? 'list' : 'map');
        } else sync();
    });
    const cancel = () => { drag = null; panel.removeAttribute('data-dragging'); sync(); };
    handle.addEventListener('pointercancel', cancel);
    handle.addEventListener('lostpointercapture', () => { if (drag) cancel(); });
    const resize = () => {
        cancelAnimationFrame(refreshFrame);
        refreshFrame = requestAnimationFrame(sync);
    };
    const observer = typeof ResizeObserver === 'function' ? new ResizeObserver(resize) : null;
    observer?.observe(root);
    if (filters) observer?.observe(filters);
    phone.addEventListener('change', resize);
    window.visualViewport?.addEventListener('resize', resize);
    window.addEventListener('resize', resize);
    sync();
    return {
        setView,
        showSelection() { hasSelection = true; setView('peek'); },
        clearSelection() { hasSelection = false; if (view === 'peek') view = 'map'; sync(); },
        setMapAvailable(value) { available = value; root.classList.toggle('is-map-unavailable', !value); setView(value ? 'map' : 'list'); },
        insets() { return phone.matches ? [16, visibleHeight(view === 'list' ? 'map' : view) + 16] : [32, 32]; },
        dispose() {
            observer?.disconnect(); cancelAnimationFrame(refreshFrame);
            phone.removeEventListener('change', resize);
            window.visualViewport?.removeEventListener('resize', resize);
            window.removeEventListener('resize', resize);
        },
    };
}
