/* Mapa y lista comparten selección y API. Solo se aplica la última consulta;
   si Leaflet falla, el listado conserva los filtros y los enlaces de los clubes. */
import { normalizeClubs, clubCount, createClubCard } from './map-data.js?v=rp06';
import { bindSportSelection, sportsFromUrl, withSports } from './sport-selection.js?v=c4f77a5ce435f2c6';
import { createClubDrawer } from './club-drawer.js?v=a164096e2174c1a2';

function initClubMap() {
    const element = document.getElementById('rp-club-map');
    if (!element) return;
    const root = document.querySelector('[data-rp-explorer]');
    const count = document.getElementById('club-count');
    const state = root.querySelector('[data-map-state]');
    const stateTitle = root.querySelector('[data-map-state-title]');
    const stateText = root.querySelector('[data-map-state-text]');
    const retry = root.querySelector('[data-map-retry]');
    const message = root.querySelector('[data-map-message]');
    const selection = root.querySelector('[data-selected-card]');
    const filterGroup = root.querySelector('[data-sport-filter="map"]');
    const filters = bindSportSelection(filterGroup, (slugs) => {
        const next = withSports(window.location.href, slugs);
        if (next.href !== window.location.href) window.history.pushState(null, '', next);
        loadClubs(slugs);
    });
    const mobileCount = root.querySelector('[data-mobile-count]');
    const clubList = root.querySelector('[data-mobile-club-list]');
    const sort = root.querySelector('[data-club-sort]');
    const phone = window.matchMedia('(max-width: 720px)');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    const L = window.L;
    let map = null, layer = null, selected = null, currentClubs = [];
    let request = null, sequence = 0, requestTimeout = 0, resizeFrame = 0;
    const markers = new Map();
    const sheet = createClubDrawer(root, () => {
        window.cancelAnimationFrame(resizeFrame);
        resizeFrame = window.requestAnimationFrame(() => map?.invalidateSize({ pan: false }));
    });

    function showState(title, text, canRetry = false) {
        state.hidden = false; stateTitle.textContent = title; stateText.textContent = text; retry.hidden = !canRetry;
        const empty = root.querySelector('[data-list-empty]');
        if (empty) empty.remove();
        if (!currentClubs.length) {
            const note = document.createElement('p'); note.dataset.listEmpty = ''; note.className = 'rp-map-message'; note.textContent = `${title}. ${text}`;
            if (canRetry) { const button = document.createElement('button'); button.type = 'button'; button.className = 'btn btn-ghost'; button.textContent = 'Reintentar'; button.addEventListener('click', () => loadClubs(filters.get())); note.append(document.createElement('br'), button); }
            clubList.append(note);
        }
    }
    if (L) {
        map = L.map(element, { zoomControl: false, scrollWheelZoom: false, zoomSnap: .25,
            zoomAnimation: !reduced.matches, fadeAnimation: !reduced.matches, markerZoomAnimation: !reduced.matches,
        }).setView([41.47, 2.10], 11);
        L.control.zoom({ position: 'bottomright' }).addTo(map);
        const tiles = L.tileLayer(element.dataset.tilesUrl, { maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);
        layer = L.layerGroup().addTo(map);
        tiles.on('tileerror', () => {
            message.textContent = 'Algunas partes del mapa no se han cargado. Puedes consultar la lista de clubes.';
            message.hidden = false;
        });
    } else {
        sheet?.setMapAvailable(false);
        message.textContent = 'El mapa no está disponible. Puedes buscar y abrir clubes desde la lista.';
        message.hidden = false;
    }

    function renderList() {
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
    }
    sort?.addEventListener('change', renderList);

    function clearSelection() {
        selected = null; map?.closePopup();
        for (const marker of markers.values()) marker.getElement()?.querySelector('.rp-map-marker')?.classList.remove('is-selected');
        selection?.replaceChildren();
        sheet?.clearSelection();
    }
    function showClub(club) {
        selected = club;
        for (const [key, marker] of markers) marker.getElement()?.querySelector('.rp-map-marker')?.classList.toggle('is-selected', key === club.url);
        selection?.replaceChildren(createClubCard(club));
        if (phone.matches) {
            sheet?.showSelection(); map?.closePopup();
            const [top, bottom] = sheet?.insets() || [24, 120];
            map?.panInside([club.lat, club.lng], { paddingTopLeft: [24, top], paddingBottomRight: [24, bottom], animate: !reduced.matches });
        } else if (map) {
            L.popup({ className: 'rp-club-popup', maxWidth: 370, minWidth: 260, offset: [0, -12], autoPanPadding: [24, 24] })
                .setLatLng([club.lat, club.lng]).setContent(createClubCard(club)).openOn(map);
        }
    }
    async function loadClubs(slugs = []) {
        const current = ++sequence;
        request?.abort(); window.clearTimeout(requestTimeout);
        const controller = new AbortController(); request = controller;
        let timedOut = false;
        requestTimeout = window.setTimeout(() => { timedOut = true; controller.abort(); }, 15000);
        clearSelection(); currentClubs = []; renderList(); markers.clear(); layer?.clearLayers();
        element.setAttribute('aria-busy', 'true');
        if (count) count.textContent = 'Buscando clubes…';
        if (mobileCount) mobileCount.textContent = 'Buscando clubes…';
        showState('Buscando clubes', 'Consultando las pistas disponibles.');
        const url = withSports(new URL(element.dataset.clubsUrl, window.location.origin), slugs);
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
            if (map) for (const club of clubs) {
                const marker = L.marker([club.lat, club.lng], { title: club.name, alt: club.name, keyboard: true,
                    icon: L.divIcon({ className: 'rp-map-marker-wrap', html: '<div class="rp-map-marker"></div>', iconSize: [24, 24], iconAnchor: [12, 12] }),
                });
                marker.on('click', () => showClub(club)); marker.addTo(layer); markers.set(club.url, marker);
            }
            if (clubs.length) {
                state.hidden = true;
                if (map) {
                    const [top, bottom] = sheet?.insets() || [32, 32];
                    map.fitBounds(L.latLngBounds(clubs.map((club) => [club.lat, club.lng])), {
                        paddingTopLeft: [32, top], paddingBottomRight: [32, bottom], maxZoom: 12, animate: false,
                    });
                }
            } else {
                const text = slugs.length ? 'Prueba otros deportes o selecciona Todos.' : 'Vuelve a consultar el mapa más tarde.';
                showState(slugs.length ? 'Sin clubes para estos deportes' : 'Todavía no hay clubes disponibles', text);
                const hint = document.createElement('p'); hint.className = 'rp-mobile-selection__hint'; hint.textContent = text;
                selection?.replaceChildren(hint); sheet?.setView('list');
            }
        } catch (error) {
            if (current !== sequence || (error.name === 'AbortError' && !timedOut)) return;
            if (count) count.textContent = 'Clubes no disponibles';
            if (mobileCount) mobileCount.textContent = 'Sin conexión';
            const text = timedOut ? 'La consulta está tardando demasiado. Vuelve a intentarlo.' : 'Comprueba tu conexión y vuelve a intentarlo.';
            showState('No se han podido cargar los clubes', text, true);
            const note = document.createElement('p'); note.className = 'rp-mobile-selection__hint'; note.textContent = text;
            const again = document.createElement('button'); again.type = 'button'; again.className = 'btn btn-ghost'; again.textContent = 'Reintentar';
            again.addEventListener('click', () => loadClubs(filters.get())); selection?.replaceChildren(note, again); sheet?.setView('list');
        } finally {
            if (current === sequence) { window.clearTimeout(requestTimeout); element.setAttribute('aria-busy', 'false'); }
        }
    }
    window.addEventListener('popstate', () => {
        filters.set(sportsFromUrl(window.location.href, filters.available));
        loadClubs(filters.get());
    });
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            filters.set(sportsFromUrl(window.location.href, filters.available));
            loadClubs(filters.get());
        }
    });
    retry.addEventListener('click', () => loadClubs(filters.get()));
    phone.addEventListener('change', () => { map?.closePopup(); if (selected && phone.matches) showClub(selected); });
    window.addEventListener('pagehide', (event) => {
        if (event.persisted) return;
        request?.abort(); window.clearTimeout(requestTimeout); window.cancelAnimationFrame(resizeFrame); sheet?.dispose();
    });
    const canonical = withSports(window.location.href, filters.get());
    if (canonical.href !== window.location.href) window.history.replaceState(null, '', canonical);
    loadClubs(filters.get());
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initClubMap, { once: true });
else initClubMap();
