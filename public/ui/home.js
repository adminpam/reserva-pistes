/* La portada consulta el API existente. Solo la última selección puede pintar
   marcadores; móvil y escritorio presentan la misma ficha de formas distintas. */
import { normalizeClubs, clubCount, createClubCard } from './map-data.js';

function initClubMap() {
    const element = document.getElementById('rp-club-map');
    if (!element) return;
    const count = document.getElementById('club-count');
    const state = document.querySelector('[data-map-state]');
    const stateTitle = document.querySelector('[data-map-state-title]');
    const stateText = document.querySelector('[data-map-state-text]');
    const retry = document.querySelector('[data-map-retry]');
    const message = document.querySelector('[data-map-message]');
    const selection = document.querySelector('[data-mobile-selection]');
    const initialHint = selection?.firstElementChild?.cloneNode(true);
    const filters = document.querySelectorAll('[name="sport-filter"]');
    const sheet = document.querySelector('[data-club-sheet]');
    const sheetContent = document.querySelector('[data-sheet-content]');
    const sheetToggle = document.querySelector('[data-sheet-toggle]');
    const mobileCount = document.querySelector('[data-mobile-count]');
    const clubList = document.querySelector('[data-mobile-club-list]');
    const sort = document.querySelector('[data-club-sort]');
    let currentClubs = [];
    const phone = window.matchMedia('(max-width: 720px)');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

    function showState(title, text, canRetry = false) {
        state.hidden = false;
        stateTitle.textContent = title;
        stateText.textContent = text;
        retry.hidden = !canRetry;
    }
    if (!window.L) {
        element.setAttribute('aria-busy', 'false');
        if (count) count.textContent = 'Mapa no disponible';
        showState('No se ha podido cargar el mapa', 'Comprueba tu conexión y vuelve a intentarlo.', true);
        retry.addEventListener('click', () => window.location.reload());
        return;
    }

    const L = window.L;
    const map = L.map(element, {
        zoomControl: false, scrollWheelZoom: false, zoomSnap: .25,
        zoomAnimation: !reduced.matches, fadeAnimation: !reduced.matches,
        markerZoomAnimation: !reduced.matches,
    }).setView([41.47, 2.10], 11);
    L.control.zoom({ position: 'bottomright' }).addTo(map);
    const tiles = L.tileLayer(element.dataset.tilesUrl, {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(map);
    const layer = L.layerGroup().addTo(map);
    const markers = new Map();
    let selected = null;
    let request = null;
    let sequence = 0;
    let requestTimeout = 0;
    let resizeFrame = 0;

    tiles.on('tileerror', () => {
        if (!message) return;
        message.textContent = 'Algunas partes del mapa no se han podido cargar. Puedes seguir consultando los clubes.';
        message.hidden = false;
    });

    function updateInsets() {
        const height = phone.matches && sheet ? Math.ceil(sheet.getBoundingClientRect().height) : 0;
        element.style.setProperty('--rp-sheet-height', `${height}px`);
    }
    function renderList() {
        if (!clubList) return;
        const ordered = [...currentClubs].sort((a, b) => sort?.value === 'price'
            ? (a.price ?? Infinity) - (b.price ?? Infinity) || a.name.localeCompare(b.name, 'es')
            : a.name.localeCompare(b.name, 'es'));
        clubList.replaceChildren(...ordered.map((club) => {
            const card = createClubCard(club);
            const price = document.createElement('div'); price.className = 'rp-club-card__price';
            if (club.price !== null) {
                const prefix = document.createElement('span'); prefix.textContent = 'desde';
                const amount = document.createTextNode(new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR', maximumFractionDigits: 2 }).format(club.price / 100));
                const unit = document.createElement('span'); unit.textContent = '/hora'; price.append(prefix, amount, unit);
            } else { const note = document.createElement('span'); note.textContent = 'Sin pistas'; price.append(note); }
            card.append(price); return card;
        }));
        updateInsets();
    }
    sort?.addEventListener('change', renderList);
    sheetToggle?.addEventListener('click', () => {
        sheetContent.hidden = !sheetContent.hidden;
        sheetToggle.setAttribute('aria-expanded', String(!sheetContent.hidden));
        updateInsets();
        map.invalidateSize({ pan: false });
    });

    function clearSelection() {
        selected = null;
        map.closePopup();
        for (const marker of markers.values()) marker.getElement()?.querySelector('.rp-map-marker')?.classList.remove('is-selected');
        if (selection && initialHint) selection.replaceChildren(initialHint.cloneNode(true));
    }

    function showClub(club) {
        selected = club;
        for (const [key, marker] of markers) marker.getElement()?.querySelector('.rp-map-marker')?.classList.toggle('is-selected', key === club.url);
        if (selection) selection.replaceChildren(createClubCard(club));
        if (phone.matches) {
            map.closePopup();
            map.panInside([club.lat, club.lng], { paddingTopLeft: [32, 100], paddingBottomRight: [32, sheet ? sheet.getBoundingClientRect().height + 24 : 38], animate: !reduced.matches });
            selection?.scrollIntoView({ block: 'nearest', behavior: reduced.matches ? 'auto' : 'smooth' });
        } else {
            L.popup({ className: 'rp-club-popup', maxWidth: 370, minWidth: 326, offset: [0, -12], autoPanPadding: [24, 24] })
                .setLatLng([club.lat, club.lng]).setContent(createClubCard(club)).openOn(map);
        }
    }

    async function loadClubs(slug = '') {
        const current = ++sequence;
        request?.abort();
        window.clearTimeout(requestTimeout);
        const controller = new AbortController();
        request = controller;
        let timedOut = false;
        requestTimeout = window.setTimeout(() => { timedOut = true; controller.abort(); }, 15000);
        clearSelection();
        currentClubs = []; renderList();
        if (mobileCount) mobileCount.textContent = 'Buscando clubes…';
        markers.clear(); layer.clearLayers();
        element.setAttribute('aria-busy', 'true');
        if (count) count.textContent = 'Buscando clubes…';
        showState('Buscando clubes', 'Consultando las pistas disponibles.');
        const url = new URL(element.dataset.clubsUrl, window.location.origin);
        if (slug) url.searchParams.set('sport', slug);

        try {
            const response = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('No se han podido consultar los clubes.');
            const payload = await response.json();
            const clubs = normalizeClubs(payload, window.location.origin);
            if (current !== sequence) return;
            if (payload.length && !clubs.length) throw new Error('Coordenadas no disponibles.');
            if (count) count.textContent = clubCount(clubs.length);
            if (mobileCount) mobileCount.textContent = clubCount(clubs.length);
            currentClubs = clubs; renderList();

            for (const club of clubs) {
                const marker = L.marker([club.lat, club.lng], {
                    title: club.name, alt: club.name, keyboard: true,
                    icon: L.divIcon({ className: 'rp-map-marker-wrap', html: '<div class="rp-map-marker"></div>', iconSize: [24, 24], iconAnchor: [12, 12] }),
                });
                marker.on('click', () => showClub(club));
                marker.addTo(layer);
                markers.set(club.url, marker);
            }
            if (clubs.length) {
                state.hidden = true;
                map.fitBounds(L.latLngBounds(clubs.map((club) => [club.lat, club.lng])), {
                    paddingTopLeft: phone.matches ? [32, 100] : [48, 48], paddingBottomRight: phone.matches ? [32, (sheet?.getBoundingClientRect().height || 0) + 24] : [48, 48], maxZoom: 12, animate: false,
                });
            } else {
                showState(slug ? 'Sin clubes para este deporte' : 'Todavía no hay clubes disponibles',
                    slug ? 'Prueba otro deporte o selecciona Todos.' : 'Vuelve a consultar el mapa más tarde.');
                if (selection) {
                    const hint = document.createElement('p'); hint.className = 'rp-mobile-selection__hint';
                    hint.textContent = 'Los clubes aparecerán aquí cuando estén disponibles.';
                    selection.replaceChildren(hint);
                }
            }
        } catch (error) {
            if (current !== sequence || (error.name === 'AbortError' && !timedOut)) return;
            if (count) count.textContent = 'Clubes no disponibles';
            if (mobileCount) mobileCount.textContent = 'Sin conexión';
            showState('No se han podido cargar los clubes',
                timedOut ? 'La consulta está tardando demasiado. Vuelve a intentarlo.' : 'Comprueba tu conexión y vuelve a intentarlo.', true);
        } finally {
            if (current === sequence) {
                window.clearTimeout(requestTimeout);
                element.setAttribute('aria-busy', 'false');
            }
        }
    }

    filters.forEach((filter) => filter.addEventListener('change', () => loadClubs(filter.value)));
    retry.addEventListener('click', () => loadClubs(document.querySelector('[name="sport-filter"]:checked')?.value || ''));
    phone.addEventListener('change', () => {
        updateInsets(); map.invalidateSize({ pan: false });
        if (selected) showClub(selected);
    });
    const resize = () => {
        window.cancelAnimationFrame(resizeFrame);
        resizeFrame = window.requestAnimationFrame(() => { updateInsets(); map.invalidateSize({ pan: false }); });
    };
    const observer = typeof ResizeObserver === 'function' ? new ResizeObserver(resize) : null;
    observer?.observe(element);
    if (sheet) observer?.observe(sheet);
    window.addEventListener('resize', resize);
    window.addEventListener('pagehide', (event) => {
        if (event.persisted) return;
        request?.abort();
        window.clearTimeout(requestTimeout);
        window.cancelAnimationFrame(resizeFrame);
        observer?.disconnect();
        window.removeEventListener('resize', resize);
    });
    loadClubs(document.querySelector('[name="sport-filter"]:checked')?.value || '');
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initClubMap, { once: true });
else initClubMap();
