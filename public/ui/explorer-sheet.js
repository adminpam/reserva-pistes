/* Три состояния мобильной панели. Высота считается по доступной области,
   а карта сохраняет своё положение при переходе к полному списку. */
export function createExplorerSheet(root, onLayout = () => {}) {
    const panel = root.querySelector('[data-club-sheet]');
    const content = root.querySelector('[data-sheet-content]');
    const header = root.querySelector('[data-sheet-header]');
    const handle = root.querySelector('[data-sheet-toggle]');
    const filters = root.querySelector('.rp-sports');
    const frame = root.querySelector('.rp-map-frame');
    const controls = [...root.querySelectorAll('[data-sheet-view]')];
    const phone = window.matchMedia('(max-width: 720px)');
    if (!panel || !content || !header || !handle) return null;
    let view = 'split', available = true, gesture = null, ignoreClick = false;
    let refreshFrame = 0;
    const order = ['map', 'split', 'list'];

    function dimensions() {
        const filterHeight = Math.ceil(filters?.getBoundingClientRect().height || 0);
        const maximum = Math.max(0, root.getBoundingClientRect().height - filterHeight);
        const minimum = Math.min(Math.ceil(header.getBoundingClientRect().height), maximum);
        return { filterHeight, maximum, minimum, split: Math.min(maximum, Math.max(minimum + 100, maximum * .48)) };
    }
    function heightFor(state, size = dimensions()) {
        return state === 'list' ? size.maximum : state === 'map' ? size.minimum : size.split;
    }
    function sync() {
        const size = dimensions();
        root.dataset.sheetState = view;
        root.style.setProperty('--rp-filter-height', `${size.filterHeight}px`);
        root.style.setProperty('--rp-sheet-size', `${heightFor(view, size)}px`);
        const isPhone = phone.matches;
        if (isPhone && view === 'map' && content.contains(document.activeElement)) handle.focus();
        content.hidden = isPhone && view === 'map';
        handle.setAttribute('aria-expanded', String(!content.hidden));
        handle.setAttribute('aria-label', view === 'list' ? 'Reducir la lista de clubes' : 'Ampliar la lista de clubes');
        for (const button of controls) {
            button.setAttribute('aria-pressed', String(button.dataset.sheetView === view));
            button.disabled = !available && button.dataset.sheetView !== 'list';
        }
        const hideMap = !available || (isPhone && view === 'list');
        if (frame) { frame.inert = hideMap; frame.setAttribute('aria-hidden', String(hideMap)); }
        const map = root.querySelector('#rp-club-map');
        if (map) map.style.setProperty('--rp-sheet-overlap', `${isPhone && view !== 'list' ? heightFor(view, size) : 0}px`);
        onLayout();
    }
    function setView(next) {
        if (!order.includes(next)) return;
        view = available ? next : 'list';
        panel.style.removeProperty('height');
        panel.removeAttribute('data-dragging');
        sync();
    }
    controls.forEach((button) => button.addEventListener('click', () => setView(button.dataset.sheetView)));
    handle.addEventListener('click', () => {
        if (ignoreClick) { ignoreClick = false; return; }
        setView(view === 'list' ? 'split' : 'list');
    });
    handle.addEventListener('keydown', (event) => {
        const offset = event.key === 'ArrowUp' ? 1 : event.key === 'ArrowDown' ? -1 : 0;
        if (!offset && !['Home', 'End', 'Escape'].includes(event.key)) return;
        event.preventDefault();
        if (event.key === 'Home' || event.key === 'Escape') setView('map');
        else if (event.key === 'End') setView('list');
        else setView(order[Math.max(0, Math.min(2, order.indexOf(view) + offset))]);
    });
    handle.addEventListener('pointerdown', (event) => {
        if (!phone.matches || !available || event.button !== 0) return;
        gesture = { id: event.pointerId, y: event.clientY, height: panel.getBoundingClientRect().height, moved: false };
        handle.setPointerCapture(event.pointerId);
    });
    handle.addEventListener('pointermove', (event) => {
        if (!gesture || gesture.id !== event.pointerId) return;
        const delta = gesture.y - event.clientY;
        if (Math.abs(delta) < 6 && !gesture.moved) return;
        gesture.moved = true;
        if (event.cancelable) event.preventDefault();
        const size = dimensions();
        panel.dataset.dragging = 'true';
        panel.style.height = `${Math.max(size.minimum, Math.min(size.maximum, gesture.height + delta))}px`;
    });
    const endGesture = (event) => {
        if (!gesture || gesture.id !== event.pointerId) return;
        const moved = gesture.moved;
        const currentHeight = panel.getBoundingClientRect().height;
        gesture = null;
        if (moved) {
            ignoreClick = true;
            const size = dimensions();
            const closest = order.reduce((best, next) => Math.abs(heightFor(next, size) - currentHeight) < Math.abs(heightFor(best, size) - currentHeight) ? next : best, 'map');
            setView(closest);
        } else sync();
    };
    handle.addEventListener('pointerup', endGesture);
    handle.addEventListener('pointercancel', () => { gesture = null; panel.style.removeProperty('height'); panel.removeAttribute('data-dragging'); sync(); });
    const resize = () => {
        window.cancelAnimationFrame(refreshFrame);
        refreshFrame = window.requestAnimationFrame(sync);
    };
    const observer = typeof ResizeObserver === 'function' ? new ResizeObserver(resize) : null;
    observer?.observe(root); if (filters) observer?.observe(filters); observer?.observe(header);
    phone.addEventListener('change', resize);
    window.visualViewport?.addEventListener('resize', resize);
    window.addEventListener('resize', resize);
    sync();

    return {
        setView,
        setMapAvailable(value) { available = value; root.classList.toggle('is-map-unavailable', !value); if (!value) view = 'list'; sync(); },
        insets() {
            const size = dimensions();
            return phone.matches ? [24, view === 'list' ? size.minimum + 20 : heightFor(view, size) + 20] : [32, 32];
        },
        dispose() {
            observer?.disconnect(); window.cancelAnimationFrame(refreshFrame);
            phone.removeEventListener('change', resize);
            window.visualViewport?.removeEventListener('resize', resize);
            window.removeEventListener('resize', resize);
        },
    };
}
