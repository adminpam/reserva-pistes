/* Actualiza las horas al volver a la pestaña. Conserva el formulario y nunca
   cambia por otra hora la selección que acaba de quedar ocupada. */
export function readAvailability(payload, expectedDate) {
    if (!payload || payload.date !== expectedDate || !Array.isArray(payload.courts)) {
        throw new Error('Respuesta de otro día o con formato incorrecto.');
    }
    const result = new Map();
    for (const court of payload.courts) {
        if (!Number.isSafeInteger(court.id) || court.id < 1 || result.has(court.id) || !Array.isArray(court.hours)) {
            throw new Error('Pista no válida en la disponibilidad.');
        }
        const hours = new Map();
        for (const slot of court.hours) {
            if (!Number.isInteger(slot.hour) || slot.hour < 0 || slot.hour > 23
                || typeof slot.available !== 'boolean' || hours.has(slot.hour)) {
                throw new Error('Hora no válida en la disponibilidad.');
            }
            hours.set(slot.hour, slot.available);
        }
        result.set(court.id, hours);
    }
    return result;
}

function updateButton(form, inputs) {
    const button = form.querySelector('button[type="submit"]');
    if (button) button.disabled = !inputs.some((input) => input.checked && !input.disabled);
}

export function applyAvailability(forms, snapshot) {
    // Comprobar la respuesta completa antes de modificar el primer formulario.
    const updates = forms.map((form) => {
        const id = Number(form.querySelector('[name="court_id"]').value);
        const hours = snapshot.get(id);
        const inputs = [...form.querySelectorAll('[name="hour"]')];
        if (!hours || inputs.length !== hours.size || inputs.some((input) => !hours.has(Number(input.value)))) {
            throw new Error('Han cambiado las pistas o sus horarios. Hay que consultar la ficha de nuevo.');
        }
        return { form, hours, inputs };
    });
    let selectionLost = false;
    for (const { form, hours, inputs } of updates) {
        for (const input of inputs) {
            input.disabled = !hours.get(Number(input.value));
            if (input.disabled && input.checked) { input.checked = false; selectionLost = true; }
        }
        updateButton(form, inputs);
        const empty = form.querySelector('[data-availability-empty]');
        if (empty) empty.hidden = inputs.some((input) => !input.disabled);
        form.dispatchEvent(new Event('change', { bubbles: true }));
    }
    return selectionLost;
}

export function watchAvailability(root) {
    const forms = [...root.querySelectorAll('[data-booking-form]')];
    if (!forms.length) return;
    const status = root.querySelector('[data-availability-status]');
    const date = root.dataset.availabilityDate;
    const endpoint = new URL(root.dataset.availabilityUrl, window.location.href);
    if (endpoint.origin !== window.location.origin) return;
    let running = false;
    let lastAttempt = 0;

    const refresh = async () => {
        if (running || document.visibilityState === 'hidden' || Date.now() - lastAttempt < 500) return;
        running = true; lastAttempt = Date.now();
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(endpoint, {
                headers: { Accept: 'application/json' }, credentials: 'same-origin',
                cache: 'no-store', signal: controller.signal,
            });
            if (!response.ok) throw new Error('No se pudo consultar la disponibilidad.');
            const data = readAvailability(await response.json(), date);
            const lost = applyAvailability(forms, data);
            if (status) status.textContent = lost
                ? 'La hora que habías elegido ya no está disponible. Elige otra.'
                : 'Disponibilidad actualizada.';
        } catch {
            if (status) status.textContent = 'No se han podido actualizar las horas. Pulsa «Ver disponibilidad» para volver a consultarlas.';
        } finally {
            window.clearTimeout(timeout); running = false;
        }
    };

    root.addEventListener('change', () => forms.forEach((form) => {
        updateButton(form, [...form.querySelectorAll('[name="hour"]')]);
    }));
    window.addEventListener('focus', refresh);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') refresh();
    });
    window.addEventListener('pageshow', (event) => { if (event.persisted) refresh(); });
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-availability-url]').forEach(watchAvailability);
}
