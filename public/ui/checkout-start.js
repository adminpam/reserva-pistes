// Cambiar la selección crea una petición nueva. Repetir el mismo envío conserva
// su clave y el servidor devuelve el mismo resultado, sin renovar la retención.
export function renewCheckoutKey(form, uuid) {
    const field = form.elements.namedItem('checkout_key');
    if (!field || typeof uuid !== 'function') return false;
    const next = uuid();
    if (!/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(next)) return false;
    field.value = next;
    return true;
}
export function mountCheckoutStart(root, uuid, page = null) {
    const forms = [...root.querySelectorAll('[data-booking-form]')];
    for (const form of forms) form.addEventListener('change', event => {
        if (['hour', 'is_open', 'min_level', 'max_level', 'date'].includes(event.target?.name)) renewCheckoutKey(form, uuid);
    });
    page?.addEventListener('pageshow', event => {
        if (event.persisted) for (const form of forms) renewCheckoutKey(form, uuid);
    });
}
if (typeof document !== 'undefined') {
    const uuid = globalThis.crypto?.randomUUID ? () => globalThis.crypto.randomUUID() : null;
    mountCheckoutStart(document, uuid, window);
}
