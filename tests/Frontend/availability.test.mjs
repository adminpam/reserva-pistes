// Casos del problema observado: otra pestaña cancela, o alguien ocupa la selección.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readAvailability, applyAvailability, watchAvailability } from '../../public/ui/availability.js';

const day = '2026-11-19';
function payload(hours = [[8, true], [9, true], [10, true]]) {
    return { date: day, courts: [{ id: 1, hours: hours.map(([hour, available]) => ({ hour, available })) }] };
}
function form(selected = 8, disabled = []) {
    const inputs = [8, 9, 10].map((hour) => ({ value: String(hour), checked: selected === hour, disabled: disabled.includes(hour) }));
    const button = { disabled: selected === null };
    const empty = { hidden: true };
    return {
        inputs, button, empty, events: 0,
        values: { is_open: true, min_level: '2', max_level: '4' },
        querySelector(selector) {
            if (selector === '[name="court_id"]') return { value: '1' };
            if (selector === 'button[type="submit"]') return button;
            if (selector === '[data-availability-empty]') return empty;
            return null;
        },
        querySelectorAll: () => inputs,
        dispatchEvent() { this.events++; },
    };
}

test('una cancelación habilita la hora y conserva la selección y los niveles', () => {
    const f = form(9, [10]);
    assert.equal(applyAvailability([f], readAvailability(payload(), day)), false);
    assert.equal(f.inputs[2].disabled, false);
    assert.equal(f.inputs[1].checked, true);
    assert.deepEqual(f.values, { is_open: true, min_level: '2', max_level: '4' });
    assert.equal(f.button.disabled, false);
});
test('si ocupan la hora elegida no selecciona otra hora automáticamente', () => {
    const f = form(10);
    assert.equal(applyAvailability([f], readAvailability(payload([[8, true], [9, true], [10, false]]), day)), true);
    assert.equal(f.inputs[2].checked, false);
    assert.equal(f.inputs.some((input) => input.checked), false);
    assert.equal(f.button.disabled, true);
});
test('un día completo pasa a tener disponibilidad sin elegir por el usuario', () => {
    const f = form(null, [8, 9, 10]); f.empty.hidden = false;
    applyAvailability([f], readAvailability(payload([[8, false], [9, false], [10, true]]), day));
    assert.equal(f.empty.hidden, true);
    assert.equal(f.button.disabled, true);
    assert.equal(f.inputs[2].disabled, false);
});
test('una respuesta de otro día o con horas inválidas se rechaza', () => {
    assert.throws(() => readAvailability({ ...payload(), date: '2026-11-20' }, day));
    assert.throws(() => readAvailability(payload([[8, 'true']]), day));
    assert.throws(() => readAvailability(payload([[8, true], [8, false]]), day));
    assert.throws(() => readAvailability(payload([[24, true]]), day));
});
test('un cambio de pistas no aplica una actualización parcial de formularios', () => {
    const first = form(8, [10]), second = form();
    second.querySelector = (selector) => selector === '[name="court_id"]' ? { value: '2' } : null;
    assert.throws(() => applyAvailability([first, second], readAvailability(payload(), day)));
    assert.equal(first.inputs[2].disabled, true);
    assert.equal(first.events, 0);
});
test('volver a dar foco consulta el servidor y libera la hora de la otra pestaña', async () => {
    const callbacks = new Map(), f = form(9, [10]), status = { textContent: '' };
    const previous = { window: globalThis.window, document: globalThis.document, fetch: globalThis.fetch };
    globalThis.window = {
        location: { href: 'https://reserva.test/clubs/1', origin: 'https://reserva.test' },
        addEventListener: (name, callback) => callbacks.set(name, callback), setTimeout, clearTimeout,
    };
    globalThis.document = { visibilityState: 'visible', addEventListener: (name, callback) => callbacks.set(name, callback) };
    let requests = 0;
    globalThis.fetch = async (url, options) => {
        requests++; assert.equal(url.origin, 'https://reserva.test'); assert.equal(options.cache, 'no-store');
        return { ok: true, json: async () => payload() };
    };
    try {
        watchAvailability({
            dataset: { availabilityDate: day, availabilityUrl: '/clubs/1/availability?date=' + day },
            querySelectorAll: () => [f], querySelector: () => status, addEventListener() {},
        });
        await callbacks.get('focus')();
        assert.equal(requests, 1); assert.equal(f.inputs[2].disabled, false);
        assert.equal(status.textContent, 'Disponibilidad actualizada.');
    } finally {
        for (const [name, value] of Object.entries(previous)) {
            if (value === undefined) delete globalThis[name]; else globalThis[name] = value;
        }
    }
});
