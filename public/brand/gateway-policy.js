/* Entrada pública estable. Solo se transporta una ruta relativa en el fragmento;
   no se cambia el origen de Laravel ni se guardan páginas o formularios. */
export const APP_ORIGIN = 'https://reserva-pistes.onrender.com';
export const ENTRY_URL = 'https://reserva-pistes-inicio.onrender.com/';
export const NAVIGATION_GRACE_MS = 800;

export function isAppPage(url) {
    if (url.origin !== APP_ORIGIN || url.username || url.password) return false;
    return /^\/(?:$|login\/?$|register\/?$|profile\/?$|matches\/?$|encuentra-partido\/?$|my\/(?:matches|bookings|payments)\/?$|clubs\/[1-9]\d*\/?$|checkout\/[a-f0-9-]{36}\/?$|admin\/(?:clubs|sports|users)\/?$|manager\/(?:courts(?:\/(?:create|[1-9]\d*\/edit))?|bookings)\/?$)/.test(url.pathname);
}

export function gatewayFor(value) {
    let target;
    try {target = new URL(value, APP_ORIGIN);} catch {return null;}
    if (!isAppPage(target)) return null;
    const relative = target.pathname + target.search + target.hash;
    if (relative.length > 4096 || /[\u0000-\u001f\u007f\\]/.test(relative)) return null;
    const entry = new URL(ENTRY_URL);entry.hash = new URLSearchParams({next:relative}).toString();return entry.href;
}

export async function appIsReady({fetcher=(...args)=>globalThis.fetch(...args), timers={set:(...args)=>globalThis.setTimeout(...args), clear:(...args)=>globalThis.clearTimeout(...args)}, timeout=NAVIGATION_GRACE_MS, now=()=>Date.now()}={}) {
    const controller = new AbortController();let timer;
    const deadline = new Promise(resolve => {timer = timers.set(() => {controller.abort();resolve(false);}, timeout);});
    const check = async () => {
        const url = new URL('/brand/ready', APP_ORIGIN);url.searchParams.set('_', String(now()));
        const response = await fetcher(url.href, {credentials:'omit', cache:'no-store', redirect:'error', signal:controller.signal, headers:{Accept:'image/svg+xml'}});
        if (!response.ok || response.redirected || !response.headers.get('Content-Type')?.toLowerCase().startsWith('image/svg+xml')) return false;
        const body = await response.text();
        return body.length <= 2048 && body.includes('xmlns="http://www.w3.org/2000/svg"') && /\bwidth=["']2["']/.test(body) && /\bheight=["']2["']/.test(body);
    };
    try {return await Promise.race([check().catch(() => false), deadline]);}
    finally {timers.clear(timer);}
}
