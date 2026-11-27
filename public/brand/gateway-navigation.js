/* Las transiciones rápidas no muestran la escena. Sin worker actualizado se
   comprueba brevemente la preparación antes de elegir el destino de un GET. */
import {APP_ORIGIN, gatewayFor, appIsReady, NAVIGATION_GRACE_MS} from './gateway-policy.js?v=5aa3abecf34a6036';
const mounted = new WeakMap();

export function mountGateway(doc=document, env=globalThis,{readiness=appIsReady}={}) {
    if (env.location.origin !== APP_ORIGIN || mounted.has(doc)) return mounted.get(doc) || null;
    let disposed = false, ownWorker = false, generation = 0;
    const workers = env.navigator?.serviceWorker;
    const isOwn = worker => {try {return new URL(worker.scriptURL).origin === APP_ORIGIN && new URL(worker.scriptURL).pathname === '/rp-gateway-sw.js';} catch {return false;}};
    const controlled = () => ownWorker && isOwn(workers?.controller) && new URL(workers.controller.scriptURL).searchParams.get('loader') === '20b';
    const sameDocument = url => url.pathname === env.location.pathname && url.search === env.location.search;
    const leave = async (url,entry) => {
        const current = ++generation;
        let ready = false;try {ready = await readiness({timeout:NAVIGATION_GRACE_MS});} catch { /* Respaldo público. */ }
        if (disposed || current !== generation) return;
        if (ready) {url.searchParams.set('rp_ready','1');env.location.assign(url.href);}
        else env.location.assign(entry);
    };
    const pagehide = () => {generation++;};
    const click = event => {
        if (event.defaultPrevented || event.isTrusted === false || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const link = event.target?.closest?.('a[href]');
        if (!link || link.hasAttribute('download') || link.target && link.target !== '_self') return;
        let url;try {url = new URL(link.href, env.location.href);} catch {return;}
        if (sameDocument(url) && url.origin === APP_ORIGIN) return;
        const entry = gatewayFor(url.href);if (!entry) {generation++;return;}
        if (controlled()) {generation++;return;}
        event.preventDefault();void leave(url,entry);
    };
    const submit = event => {
        if (event.defaultPrevented || event.isTrusted === false) return;
        const form = event.target, button = event.submitter;
        const method = button?.getAttribute('formmethod') || form?.method || 'get';
        const target = button?.getAttribute('formtarget') || form?.target;
        if (target && target !== '_self') return;
        if (method.toLowerCase() !== 'get') {generation++;return;}
        let url;try {url = new URL(button?.getAttribute('formaction') || form.action, env.location.href);} catch {return;}
        if (!gatewayFor(url.href)) return;
        const query = new URLSearchParams();
        try {
            for (const [key,value] of new env.FormData(form)) {if (typeof value !== 'string') return;query.append(key,value);}
            if (button?.name) query.append(button.name, button.value);
        } catch {return;}
        url.search = query.toString();const entry = gatewayFor(url.href);if (!entry) return;
        if (controlled()) {generation++;return;}
        event.preventDefault();void leave(url,entry);
    };
    doc.addEventListener('click', click);doc.addEventListener('submit', submit);env.addEventListener?.('pagehide', pagehide);
    const ready = (async () => {
        if (!workers) return false;
        try {
            const existing = await workers.getRegistration('/');
            if (disposed || existing && ![existing.active,existing.waiting,existing.installing].filter(Boolean).every(isOwn)) return false;
            const registration = await workers.register('/rp-gateway-sw.js?loader=20b', {scope:'/', type:'module', updateViaCache:'none'});
            if (!disposed) ownWorker = [registration.active,registration.waiting,registration.installing].filter(Boolean).some(isOwn);
            return ownWorker;
        } catch {return false;}
    })();
    const destroy = () => {disposed = true;generation++;env.removeEventListener?.('pagehide', pagehide);doc.removeEventListener('click', click);doc.removeEventListener('submit', submit);mounted.delete(doc);};
    const binding = {ready,destroy};mounted.set(doc,binding);return binding;
}
if (typeof document !== 'undefined') mountGateway(document);
