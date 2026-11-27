/** Pruebas sin dependencias de la coordinación de carga. Se simulan el DOM,
 * las imágenes de comprobación y el reloj; no sustituyen la revisión visual. */
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import vm from 'node:vm';

const code = await readFile(new URL('../../public/brand/loading.js', import.meta.url), 'utf8');

function setup(mode, options = {}) {
    let now = 0;
    let nextId = 0;
    const timers = new Map();
    const listeners = new Map();
    const images = [];
    const redirects = [];
    const stored = new Map();
    const classList = () => {
        const values = new Set();
        return { add: (...names) => names.forEach((name) => values.add(name)),
            remove: (...names) => names.forEach((name) => values.delete(name)),
            contains: (name) => values.has(name) };
    };
    const element = () => ({ hidden: true, textContent: '', inert: false, classList: classList(),
        setAttribute(name) { this[name] = true; }, removeAttribute(name) { delete this[name]; } });
    const nodes = Object.fromEntries(['status', 'notice', 'actions', 'retry', 'direct'].map((name) => [name, element()]));
    const clicks = new Map();
    nodes.retry.addEventListener = (name, callback) => clicks.set(name, callback);
    const loader = { ...element(), hidden: false,
        dataset: { mode, return:options.current?'current':undefined, destination: options.destination || 'https://reserva-pistes.onrender.com/' },
        querySelector: (selector) => nodes[selector.slice(9, -1)] };
    const root = { classList: classList() };
    root.classList.add('rp-loading');
    const header = element();
    let fontsDone;
    const fontsReady = new Promise((resolve) => { fontsDone = resolve; });
    const document = { documentElement: root, readyState: 'loading', fonts: { ready: fontsReady },
        querySelector: () => loader,
        querySelectorAll: (selector) => selector === '[data-rp-inert]' ? (header['data-rp-inert'] ? [header] : []) : [header] };
    const setTimeout = (callback, delay = 0) => {
        const id = ++nextId; timers.set(id, { at: now + delay, callback }); return id;
    };
    const window = {
        setTimeout, clearTimeout: (id) => timers.delete(id),
        requestAnimationFrame: (callback) => setTimeout(callback, 16),
        cancelAnimationFrame: (id) => timers.delete(id),
        matchMedia: () => ({ matches: !!options.reduced }),
        addEventListener: (name, callback) => listeners.set(name, callback),
        location: { href:options.href || 'https://reserva-pistes-inicio.onrender.com/', hash:options.hash || '', replace: (url) => redirects.push(url) },
    };
    class FakeImage {
        constructor() { images.push(this); }
        set src(value) { this.url = value; }
        get src() { return this.url; }
    }
    const sessionStorage = { setItem(key, value) {
        if (options.storageDenied) throw new Error('Storage denied'); stored.set(key, value);
    } };
    vm.runInNewContext(code, { document, window, sessionStorage, Image: FakeImage, URL, URLSearchParams, Date: { now: () => now } });
    const flush = async () => { for (let i = 0; i < 8; i++) await Promise.resolve(); };
    const advance = async (milliseconds) => {
        const limit = now + milliseconds;
        await flush();
        while (true) {
            const candidates = [...timers.entries()].filter(([, timer]) => timer.at <= limit).sort((a, b) => a[1].at - b[1].at);
            if (!candidates.length) break;
            const [id, timer] = candidates[0]; timers.delete(id); now = timer.at;
            timer.callback(); await flush();
        }
        now = limit; await flush();
    };
    return { root, loader, nodes, header, images, redirects, stored, fontsDone, listeners, advance,
        retry: () => clicks.get('click')(),
        respond: async (width = 2, height = 2) => {
            const image = images.at(-1); image.naturalWidth = width; image.naturalHeight = height;
            image.onload?.(); await flush();
        }, fail: async () => { images.at(-1).onerror?.(); await flush(); } };
}

test('la página espera al documento y a las fuentes, y libera el foco', async () => {
    const s = setup('page');
    assert.equal(s.header.inert, true);
    await s.advance(1000);
    assert.equal(s.loader.hidden, false);
    s.listeners.get('load')();
    await s.advance(1000);
    assert.equal(s.loader.hidden, false);
    s.fontsDone(); await s.advance(40);
    assert.equal(s.loader.hidden, true);
    assert.equal(s.header.inert, false);
    assert.equal(s.root.classList.contains('rp-loading'), false);
    assert.equal(s.stored.get('rp-loader-seen'), '1');
});

test('los recursos externos lentos no bloquean el documento indefinidamente', async () => {
    const s = setup('page', { storageDenied: true });
    await s.advance(12040);
    assert.equal(s.loader.hidden, true);
    assert.equal(s.header.inert, false);
});

test('una respuesta de error o dimensiones inesperadas no redirige', async () => {
    const s = setup('gateway');
    assert.match(s.images[0].src, /^https:\/\/reserva-pistes\.onrender\.com\/brand\/ready\?/);
    await s.fail(); await s.advance(3000);
    await s.respond(1, 1);
    assert.equal(s.redirects.length, 0);
    await s.advance(3000); await s.respond();
    assert.deepEqual(s.redirects, ['https://reserva-pistes.onrender.com/?rp_ready=1']);
    assert.equal(s.loader.classList.contains('is-ready'), true);
});

test('un servicio sin respuesta termina en reintento, no en espera infinita', async () => {
    const s = setup('gateway');
    await s.advance(120000);
    assert.equal(s.nodes.actions.hidden, false);
    assert.equal(s.loader.classList.contains('is-error'), true);
    assert.equal(s.redirects.length, 0);
    const count = s.images.length;
    await s.advance(90000);
    assert.equal(s.images.length, count);
    s.retry();
    assert.equal(s.nodes.actions.hidden, true);
    assert.equal(s.images.length, count + 1);
    await s.respond();
    assert.equal(s.redirects.length, 1);
});

test('un callback atrasado del intento cancelado no abre la aplicación', async () => {
    const s = setup('gateway');
    const oldImage = s.images[0];
    const oldCallback = oldImage.onload;
    await s.advance(120000);
    s.retry();
    oldImage.naturalWidth = 2; oldImage.naturalHeight = 2;
    oldCallback(); await s.advance(0);
    assert.equal(s.redirects.length, 0);
    await s.respond();
    assert.equal(s.redirects.length, 1);
});

test('el movimiento reducido mantiene la comprobación de disponibilidad', async () => {
    const s = setup('gateway', { reduced: true });
    assert.equal(s.loader.classList.contains('is-reduced'), true);
    await s.respond();
    assert.equal(s.redirects.length, 1);
});

test('la vista previa permanece visible sin consultar el servidor', async () => {
    const s = setup('preview');
    await s.advance(180000);
    assert.equal(s.images.length, 0);
    assert.equal(s.loader.hidden, false);
    assert.equal(s.redirects.length, 0);
});

test('un destino sin HTTPS se rechaza sin enviar peticiones', () => {
    const s = setup('gateway', { destination: 'http://example.test/' });
    assert.equal(s.images.length, 0);
    assert.equal(s.nodes.notice.hidden, false);
});


test('la entrada conserva ruta, filtros y ancla después de recibir la imagen válida', async () => {
    const relative = '/clubs/7?date=2026-11-28&sports%5B0%5D=padel#court-9';
    const s = setup('gateway', {hash:'#'+new URLSearchParams({next:relative})});await s.respond();
    const url = new URL(s.redirects[0]);assert.equal(url.pathname,'/clubs/7');assert.equal(url.searchParams.get('sports[0]'),'padel');assert.equal(url.hash,'#court-9');assert.equal(url.searchParams.get('rp_ready'),'1');
});
test('un fragmento externo, protocol-relative o con barras inversas no cambia el origen', async () => {
    for (const next of ['https://externo.test/','//externo.test/','/\\externo.test/','/profile'+String.fromCharCode(10)]) {
        const s=setup('gateway',{hash:'#'+new URLSearchParams({next})});await s.respond();assert.equal(s.redirects[0],'https://reserva-pistes.onrender.com/?rp_ready=1');
    }
});
test('volver desde la caché del navegador reinicia una comprobación cancelada', async () => {
    const s=setup('gateway');s.listeners.get('pagehide')();s.listeners.get('pageshow')({persisted:true});assert.equal(s.images.length,2);await s.respond();assert.equal(s.redirects.length,1);
});


test('la escena local conserva la URL completa y continúa una vez con rp_ready', async () => {
    const s=setup('gateway',{current:true,href:'https://reserva-pistes.onrender.com/clubs/7?date=2026-11-28&sports%5B0%5D=padel#court-9'});
    assert.equal(s.redirects.length,0);await s.respond();
    const target=new URL(s.redirects[0]);assert.equal(target.pathname,'/clubs/7');assert.equal(target.searchParams.get('date'),'2026-11-28');assert.equal(target.hash,'#court-9');assert.equal(target.searchParams.get('rp_ready'),'1');
});
test('el enlace manual omite otra escena incluso si el servidor aún no respondió',()=>{
    const s=setup('gateway',{current:true,href:'https://reserva-pistes.onrender.com/profile'});
    assert.equal(s.nodes.direct.href,'https://reserva-pistes.onrender.com/profile?rp_ready=1');assert.equal(s.redirects.length,0);
});
test('abrir el HTML público directamente continúa hacia la portada sin bucles',async()=>{
    const s=setup('gateway',{current:true,href:'https://reserva-pistes.onrender.com/brand/navigation-shell.html'});await s.respond();assert.equal(s.redirects[0],'https://reserva-pistes.onrender.com/?rp_ready=1');
});
