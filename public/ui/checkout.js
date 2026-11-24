// Solo ayuda a rellenar datos ficticios y evita dobles clics. Decide el servidor.
export function remainingSeconds(expires, serverNow, elapsedMs = 0) {
    const end = Date.parse(expires), start = Date.parse(serverNow);
    if (!Number.isFinite(end) || !Number.isFinite(start) || !Number.isFinite(elapsedMs)) return 0;
    return Math.max(0, Math.ceil((end - start - Math.max(0, elapsedMs)) / 1000));
}
export function demoValues(number, now = new Date()) {
    if (!['4242424242424242', '4000000000000002'].includes(number)) return null;
    return {holder: 'Persona Demo', number, expiry: `12/${String((now.getUTCFullYear() + 2) % 100).padStart(2, '0')}`, cvc: '123'};
}
export function mountCheckout(root, clock = {now: () => performance.now()}, schedule = (fn) => setInterval(fn, 1000)) {
    const form = root.querySelector('[data-payment-form]'), timer = root.querySelector('[data-checkout-clock]');
    if (!form || !timer) return null;
    const button = root.querySelector('[data-pay-button]'), output = root.querySelector('[data-countdown]');
    const expiredMessage = root.querySelector('[data-expiry-message]');
    const began = clock.now();
    let sending = false;
    const tick = () => {
        const left = remainingSeconds(timer.dataset.expires, timer.dataset.serverNow, clock.now() - began);
        if (output) output.textContent = `${Math.floor(left / 60)}:${String(left % 60).padStart(2, '0')}`;
        if (expiredMessage) expiredMessage.hidden = left > 0;
        button.disabled = sending || left === 0;
        return left;
    };
    for (const tool of root.querySelectorAll('[data-demo-tools]')) tool.hidden = false;
    for (const fill of root.querySelectorAll('[data-demo-card]')) {
        fill.addEventListener('click', () => {
            if (sending || tick() === 0) return;
            const values = demoValues(fill.dataset.demoCard, new Date(timer.dataset.serverNow));
            if (!values) return;
            for (const [key, value] of Object.entries(values)) {
                const field = form.elements.namedItem(`payment[${key}]`);
                if (field) field.value = value;
            }
            button.focus();
        });
    }
    form.addEventListener('submit', event => {
        if (sending || tick() === 0 || !form.checkValidity()) {event.preventDefault(); return;}
        sending = true;
        button.disabled = true;
        button.textContent = 'Comprobando pago de prueba…';
    });
    tick();
    const interval = schedule(tick);
    return {tick, interval};
}
if (typeof document !== 'undefined') mountCheckout(document);
// Volver desde el historial carga el estado confirmado del servidor, sin reenviar.
if (typeof window !== 'undefined') window.addEventListener('pageshow', event => {
    if (event.persisted) window.location.reload();
});
