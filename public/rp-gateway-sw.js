/* Devuelve primero una escena pública verificada, nunca HTML de una cuenta.
   La lectura real se hace después del ready. POST/PATCH/DELETE siguen nativos. */
import {APP_ORIGIN, gatewayFor} from './brand/gateway-policy.js?v=de54935532b129a1';

export const SHELL_PATH = '/brand/navigation-shell.html';
export const SHELL_DIGEST = '152961a593fd7e621b40e62617f2e5941d189ef8d0d80fab3ba7e7bbdcbc10ba';
export const CACHE_PREFIX = 'rp-public-loader-';
export const CACHE_NAME = CACHE_PREFIX + SHELL_DIGEST;
const SHELL_URL = APP_ORIGIN + SHELL_PATH;
const sha = async bytes => Array.from(new Uint8Array(await crypto.subtle.digest('SHA-256',bytes)),byte=>byte.toString(16).padStart(2,'0')).join('');

export async function installShell({cacheStorage=caches, fetcher=(...args)=>globalThis.fetch(...args)}={}) {
    const url = new URL(SHELL_URL);url.searchParams.set('v',SHELL_DIGEST);
    const response = await fetcher(url.href,{credentials:'omit',cache:'reload',redirect:'error'});
    if (!response.ok || response.redirected || !response.headers.get('Content-Type')?.toLowerCase().startsWith('text/html')) throw new Error('Escena pública no disponible.');
    const bytes = await response.arrayBuffer();
    if (await sha(bytes) !== SHELL_DIGEST) throw new Error('La escena pública no coincide con esta versión.');
    const cache = await cacheStorage.open(CACHE_NAME);
    // Se descartan las cabeceras originales: el caché solo conserva esta escena.
    await cache.put(SHELL_URL,new Response(bytes,{headers:{'Content-Type':'text/html; charset=utf-8','X-RP-Public-Loader':SHELL_DIGEST}}));
}

export async function cleanShellCaches(cacheStorage=caches) {
    const names = await cacheStorage.keys();
    await Promise.all(names.filter(name=>name.startsWith(CACHE_PREFIX)&&name!==CACHE_NAME).map(name=>cacheStorage.delete(name)));
}

export function guardedNavigation(request) {
    if (request.method !== 'GET' || request.mode !== 'navigate') return false;
    try {
        const url = new URL(request.url);
        // La escena y la entrada estática usan este indicador existente tras ready.
        return url.origin === APP_ORIGIN && gatewayFor(request.url) !== null && url.searchParams.get('rp_ready') !== '1';
    } catch {return false;}
}

export async function navigate(request,{cacheStorage=globalThis.caches,fetcher=(...args)=>globalThis.fetch(...args)}={}) {
    if (!guardedNavigation(request)) return fetcher(request);
    try {
        const cache = await cacheStorage.open(CACHE_NAME), shell = await cache.match(SHELL_URL);
        if (shell?.headers.get('X-RP-Public-Loader')===SHELL_DIGEST) {
            // No hay una petición al servidor antes de esta respuesta.
            return new Response(shell.body,{headers:{
                'Content-Type':'text/html; charset=utf-8','Cache-Control':'private, no-store',
                'X-Content-Type-Options':'nosniff','Referrer-Policy':'no-referrer',
                'Content-Security-Policy':`default-src 'none'; style-src 'unsafe-inline'; script-src 'unsafe-inline'; font-src data:; img-src ${APP_ORIGIN}; base-uri 'none'; form-action 'none'; frame-ancestors 'none'`,
            }});
        }
    } catch { /* Caché no permitido: la entrada estática sigue siendo válida. */ }
    return Response.redirect(gatewayFor(request.url),302);
}

if (typeof self !== 'undefined' && typeof self.skipWaiting === 'function') {
    self.addEventListener('install',event=>event.waitUntil(installShell().then(()=>self.skipWaiting())));
    self.addEventListener('activate',event=>event.waitUntil(cleanShellCaches().then(()=>self.clients.claim())));
    self.addEventListener('fetch',event=>{if(guardedNavigation(event.request))event.respondWith(navigate(event.request));});
}
