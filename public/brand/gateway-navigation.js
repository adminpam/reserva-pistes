/* Registra la protección de recargas. Cuando no hay un worker propio disponible,
   enlaces y búsquedas GET pasan por la entrada estática conservando su destino. */
import {APP_ORIGIN, gatewayFor} from './gateway-policy.js?v=de54935532b129a1';
const mounted = new WeakMap();

export function mountGateway(doc=document, env=globalThis) {
    if (env.location.origin !== APP_ORIGIN || mounted.has(doc)) return mounted.get(doc) || null;
    let disposed = false, ownWorker = false;
    const workers = env.navigator?.serviceWorker;
    const isOwn = worker => {try {return new URL(worker.scriptURL).origin === APP_ORIGIN && new URL(worker.scriptURL).pathname === '/rp-gateway-sw.js';} catch {return false;}};
    const controlled = () => ownWorker && isOwn(workers?.controller);
    const sameDocument = url => url.pathname === env.location.pathname && url.search === env.location.search;
    const click = event => {
        if (controlled() || event.defaultPrevented || event.isTrusted === false || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const link = event.target?.closest?.('a[href]');
        if (!link || link.hasAttribute('download') || link.target && link.target !== '_self') return;
        let url;try {url = new URL(link.href, env.location.href);} catch {return;}
        if (sameDocument(url) && url.origin === APP_ORIGIN) return;
        const entry = gatewayFor(url.href);if (!entry) return;
        event.preventDefault();env.location.assign(entry);
    };
    const submit = event => {
        if (controlled() || event.defaultPrevented || event.isTrusted === false) return;
        const form = event.target, button = event.submitter;
        const method = button?.getAttribute('formmethod') || form?.method || 'get';
        const target = button?.getAttribute('formtarget') || form?.target;
        if (method.toLowerCase() !== 'get' || target && target !== '_self') return;
        let url;try {url = new URL(button?.getAttribute('formaction') || form.action, env.location.href);} catch {return;}
        if (!gatewayFor(url.href)) return;
        const query = new URLSearchParams();
        try {
            for (const [key,value] of new env.FormData(form)) {if (typeof value !== 'string') return;query.append(key,value);}
            if (button?.name) query.append(button.name, button.value);
        } catch {return;}
        url.search = query.toString();const entry = gatewayFor(url.href);if (!entry) return;
        event.preventDefault();env.location.assign(entry);
    };
    doc.addEventListener('click', click);doc.addEventListener('submit', submit);
    const ready = (async () => {
        if (!workers) return false;
        try {
            const existing = await workers.getRegistration('/');
            if (disposed || existing && ![existing.active,existing.waiting,existing.installing].filter(Boolean).every(isOwn)) return false;
            const registration = await workers.register('/rp-gateway-sw.js', {scope:'/', type:'module', updateViaCache:'none'});
            if (!disposed) ownWorker = [registration.active,registration.waiting,registration.installing].filter(Boolean).some(isOwn);
            return ownWorker;
        } catch {return false;}
    })();
    const destroy = () => {disposed = true;doc.removeEventListener('click', click);doc.removeEventListener('submit', submit);mounted.delete(doc);};
    const binding = {ready,destroy};mounted.set(doc,binding);return binding;
}
if (typeof document !== 'undefined') mountGateway(document);
