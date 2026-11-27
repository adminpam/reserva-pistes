/* Protección de navegaciones GET tras una visita al sitio. Sin CacheStorage,
   sin HTML privado almacenado y sin interceptar ni repetir POST/PATCH/DELETE. */
import {APP_ORIGIN, gatewayFor, appIsReady} from './brand/gateway-policy.js?v=de54935532b129a1';

export function guardedNavigation(request) {
    if (request.method !== 'GET' || request.mode !== 'navigate') return false;
    try {return new URL(request.url).origin === APP_ORIGIN && gatewayFor(request.url) !== null;} catch {return false;}
}

export async function navigate(request, {ready=appIsReady, fetcher=(...args)=>globalThis.fetch(...args)}={}) {
    if (!guardedNavigation(request)) return fetcher(request);
    const fallback = () => Response.redirect(gatewayFor(request.url), 302);
    try {
        if (!await ready()) return fallback();
        // Se conserva la petición original y su sesión. No se reintenta esta lectura.
        // Una respuesta de error de Laravel se muestra tal cual para evitar bucles.
        return await fetcher(request);
    } catch {return fallback();}
}

if (typeof self !== 'undefined' && typeof self.skipWaiting === 'function') {
    self.addEventListener('install', event => event.waitUntil(self.skipWaiting()));
    self.addEventListener('activate', event => event.waitUntil(self.clients.claim()));
    self.addEventListener('fetch', event => {
        if (guardedNavigation(event.request)) event.respondWith(navigate(event.request));
    });
}
