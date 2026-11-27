/* Carga compartida. En Laravel espera al documento; en la entrada estática
   comprueba una imagen generada por Laravel. Una respuesta HTML de Render
   no es una imagen válida y nunca provoca una redirección prematura. */
(() => {
    'use strict';

    const root = document.documentElement;
    const loader = document.querySelector('[data-rp-loader]');
    if (!loader) return;

    const mode = loader.dataset.mode || 'page';
    const status = loader.querySelector('[data-rp-status]');
    const notice = loader.querySelector('[data-rp-notice]');
    const actions = loader.querySelector('[data-rp-actions]');
    const retry = loader.querySelector('[data-rp-retry]');
    const direct = loader.querySelector('[data-rp-direct]');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    let closed = false;
    let frame = 0;
    let finishTimer = 0;

    const announce = (message) => { if (status) status.textContent = message; };

    function finish() {
        if (closed) return;
        closed = true;
        window.clearTimeout(finishTimer);
        try { sessionStorage.setItem('rp-loader-seen', '1'); } catch { /* Almacenamiento opcional. */ }
        loader.classList.add('is-ready');
        announce('Página preparada.');
        // Dos fotogramas permiten pintar la barra completa antes de retirarla.
        frame = window.requestAnimationFrame(() => window.requestAnimationFrame(() => {
            root.classList.remove('rp-loading');
            loader.hidden = true;
            document.querySelectorAll('[data-rp-inert]').forEach((element) => {
                element.inert = false;
                element.removeAttribute('data-rp-inert');
            });
        }));
    }

    if (mode === 'preview') {
        announce('Vista previa de la pantalla de carga.');
        return;
    }

    if (mode === 'page') {
        if (!root.classList.contains('rp-loading')) return;
        document.querySelectorAll('body > header, body > main, body > footer, body > nav').forEach((element) => {
            if (!element.inert) {
                element.inert = true;
                element.setAttribute('data-rp-inert', '');
            }
        });
        const fontsReady = document.fonts ? document.fonts.ready.catch(() => {}) : Promise.resolve();
        const assetsReady = document.readyState === 'complete'
            ? Promise.resolve()
            : new Promise((resolve) => window.addEventListener('load', resolve, { once: true }));
        // La red externa no debe impedir entrar a una página que ya es utilizable.
        finishTimer = window.setTimeout(finish, 12000);
        Promise.all([fontsReady, assetsReady]).then(finish);
        window.addEventListener('pageshow', (event) => { if (event.persisted) finish(); });
        return;
    }

    if (mode !== 'gateway') return;
    let destination;
    try {
        destination = new URL(loader.dataset.destination);
        if (destination.protocol !== 'https:') throw new Error('HTTPS obligatorio');
    } catch {
        announce('La dirección de la aplicación no es válida.');
        if (notice) { notice.hidden = false; notice.textContent = 'No se ha podido abrir la aplicación.'; }
        return;
    }
    // El origen está fijado en el archivo. El fragmento solo puede aportar una
    // ruta local: conserva la página solicitada sin permitir redirecciones externas.
    try {
        const relative = new URLSearchParams((window.location.hash || '').slice(1)).get('next');
        if (relative && relative.length <= 4096 && relative.startsWith('/') && !relative.startsWith('//') && !/[\\\u0000-\u001f\u007f]/.test(relative)) {
            const next = new URL(relative, destination.origin);
            if (next.origin === destination.origin && !next.username && !next.password) destination = next;
        }
    } catch { /* Fragmento inválido: abrir la portada configurada. */ }
    if (direct) direct.href = destination.href;
    let epoch = 0;
    let nextAttempt = 0;
    let activeProbe = null;
    let probeTimer = 0;
    let activeCancel = null;
    let deadlineTimer = 0;
    let watchdog = 0;

    function cancelProbe() {
        window.clearTimeout(probeTimer);
        if (activeProbe) {
            activeProbe.onload = null;
            activeProbe.onerror = null;
            activeProbe = null;
        }
        if (activeCancel) { const cancel = activeCancel; activeCancel = null; cancel(); }
    }

    function probe(token) {
        return new Promise((resolve) => {
            const image = new Image();
            activeProbe = image;
            let settled = false;
            const settle = (ok) => {
                if (settled) return;
                settled = true;
                window.clearTimeout(probeTimer);
                image.onload = null;
                image.onerror = null;
                activeProbe = null;
                activeCancel = null;
                resolve(ok && token === epoch);
            };
            activeCancel = () => settle(false);
            image.onload = () => settle(image.naturalWidth === 2 && image.naturalHeight === 2);
            image.onerror = () => settle(false);
            probeTimer = window.setTimeout(() => settle(false), 15000);
            const url = new URL('/brand/ready', destination.origin);
            url.searchParams.set('_', `${Date.now()}-${token}`);
            image.src = url.href;
        });
    }

    function showError(token) {
        if (token !== epoch || closed) return;
        epoch++;
        window.clearTimeout(nextAttempt);
        window.clearTimeout(watchdog);
        window.clearTimeout(deadlineTimer);
        cancelProbe();
        loader.classList.add('is-error');
        if (notice) {
            notice.hidden = false;
            notice.textContent = 'Está tardando más de lo esperado. Puedes volver a intentarlo.';
        }
        if (actions) actions.hidden = false;
        announce('La aplicación todavía no responde.');
    }

    async function attempt(token, deadline) {
        if (token !== epoch || closed) return;
        if (Date.now() >= deadline) { showError(token); return; }
        const ready = await probe(token);
        if (token !== epoch || closed) return;
        if (ready) {
            closed = true;
            window.clearTimeout(watchdog);
            window.clearTimeout(deadlineTimer);
            loader.classList.add('is-ready');
            announce('Aplicación preparada. Abriendo Reserva Pistes.');
            // Evita repetir la pantalla de carga después de esta entrada.
            destination.searchParams.set('rp_ready', '1');
            window.location.replace(destination.href);
            return;
        }
        nextAttempt = window.setTimeout(() => attempt(token, deadline), 3000);
    }

    function start() {
        epoch++;
        window.clearTimeout(nextAttempt);
        window.clearTimeout(watchdog);
        window.clearTimeout(deadlineTimer);
        cancelProbe();
        closed = false;
        const token = epoch;
        loader.classList.remove('is-error', 'is-ready');
        if (notice) notice.hidden = true;
        if (actions) actions.hidden = true;
        announce('Preparando Reserva Pistes.');
        watchdog = window.setTimeout(() => {
            if (token !== epoch || closed) return;
            if (notice) {
                notice.hidden = false;
                notice.textContent = 'Estamos preparando las pistas. Puede tardar un momento.';
            }
        }, 20000);
        deadlineTimer = window.setTimeout(() => showError(token), 120000);
        attempt(token, Date.now() + 120000);
    }

    retry?.addEventListener('click', start);
    window.addEventListener('pagehide', () => {
        epoch++;
        window.clearTimeout(nextAttempt);
        window.clearTimeout(watchdog);
        window.clearTimeout(deadlineTimer);
        cancelProbe();
        window.cancelAnimationFrame(frame);
    });
    window.addEventListener('pageshow', (event) => { if (event.persisted) start(); });
    // CSS reduce el movimiento; la comprobación del servidor sigue funcionando.
    if (reduced.matches) loader.classList.add('is-reduced');
    start();
})();
