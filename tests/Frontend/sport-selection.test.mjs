import test from 'node:test';
import assert from 'node:assert/strict';
import { canonicalSports, sportsFromUrl, withSports, bindSportSelection } from '../../public/ui/sport-selection.js';

const origin = 'http://localhost:8000';
const available = ['tenis', 'padel', 'badminton'];

test('la selección admite uno o varios, elimina duplicados y limita al catálogo', () => {
    const input = ['tenis', 'padel', 'tenis', 'inexistente', null];
    assert.deepEqual(canonicalSports(input, available), ['padel', 'tenis']);
    assert.equal(input.length, 5);
    assert.deepEqual(canonicalSports(['badminton'], available), ['badminton']);
});

test('restaura los enlaces nuevos y los índices que genera Laravel', () => {
    assert.deepEqual(sportsFromUrl(origin + '/?sports[]=tenis&sports[]=padel', available), ['padel', 'tenis']);
    assert.deepEqual(sportsFromUrl(origin + '/?sports[0]=padel&sports[1]=tenis', available), ['padel', 'tenis']);
});

test('restaura enlaces antiguos y representa la selección vacía sin filtros', () => {
    assert.deepEqual(sportsFromUrl(origin + '/?sport=tenis', available), ['tenis']);
    assert.deepEqual(sportsFromUrl(origin + '/', available), []);
    assert.deepEqual(sportsFromUrl(origin + '/?sports[]=inexistente', available), []);
});

test('no mezcla sport antiguo con sports y conserva club, nivel, fecha y fragmento', () => {
    const before = new URL(origin + '/matches?sport=tenis&sports[0]=badminton&club=Demo&my_level=1&date=2026-11-30#partidos');
    const next = withSports(before, ['tenis', 'padel', 'padel']);
    assert.equal(before.searchParams.get('sport'), 'tenis');
    assert.equal(next.searchParams.has('sport'), false);
    assert.equal(next.searchParams.has('sports[0]'), false);
    assert.deepEqual(next.searchParams.getAll('sports[]'), ['padel', 'tenis']);
    assert.equal(next.searchParams.get('club'), 'Demo');
    assert.equal(next.searchParams.get('my_level'), '1');
    assert.equal(next.searchParams.get('date'), '2026-11-30');
    assert.equal(next.hash, '#partidos');
});

test('Limpiar elimina cada parámetro de deporte y deja los otros filtros', () => {
    const next = withSports(origin + '/matches?sports[0]=tenis&sports[]=padel&club=Demo&my_level=1', []);
    assert.equal(next.searchParams.get('club'), 'Demo');
    assert.equal(next.searchParams.get('my_level'), '1');
    assert.deepEqual([...next.searchParams.keys()], ['club', 'my_level']);
});

function fixture(initial = [], catalog = available) {
    const options = catalog.map((value) => ({ value, checked: initial.includes(value), focus() { this.focused = true; } }));
    const events = new Map();
    const clearEvents = new Map();
    const attrs = {};
    const clear = { hidden: true, addEventListener: (key, fn) => clearEvents.set(key, fn) };
    const guide = { hidden: false }, caption = { hidden: true, textContent: '' };
    const summary = { textContent: '' };
    const group = {
        querySelectorAll: () => options,
        querySelector: (selector) => ({ '[data-sport-clear]': clear, '[data-sport-guide]': guide, '[data-sport-caption]': caption, '[data-sport-summary]': summary })[selector],
        addEventListener: (key, fn) => events.set(key, fn),
    };
    const changes = [];
    const binding = bindSportSelection(group, (value) => changes.push(value));
    return { options, group, clearButton: clear, guide, caption, attrs, summary, changes, binding,
        toggle(value) { const input = options.find((option) => option.value === value); input.checked = !input.checked; events.get('change')({ target: input }); },
        clear() { clearEvents.get('click')(); } };
}

test('añadir el segundo deporte conserva el primero y comunica ambas opciones', () => {
    const f = fixture();
    f.toggle('tenis'); f.toggle('padel');
    assert.deepEqual(f.binding.get(), ['padel', 'tenis']);
    assert.equal(f.options.find((input) => input.value === 'tenis').checked, true);
    assert.equal(f.guide.hidden, true);
    assert.equal(f.clearButton.hidden, false);
    assert.equal(f.summary.textContent, '2 deportes seleccionados');
    assert.deepEqual(f.changes, [['tenis'], ['padel', 'tenis']]);
});

test('desmarcar el último vuelve a mostrar todos los clubes sin bloquear el formulario', () => {
    const f = fixture(['tenis']); f.toggle('tenis');
    assert.deepEqual(f.binding.get(), []);
    assert.equal(f.guide.hidden, false);
    assert.equal(f.clearButton.hidden, true);
    assert.equal(f.summary.textContent, 'Sin filtro de deporte');
});

test('Limpiar desmarca las opciones y repetirlo no duplica consultas', () => {
    const f = fixture(['tenis', 'padel']); f.clear(); f.clear();
    assert.deepEqual(f.changes, [[]]);
    assert.equal(f.options.some((input) => input.checked), false);
    assert.equal(f.clearButton.hidden, true);
    assert.equal(f.guide.hidden, false);
    assert.equal(f.options[0].focused, true);
});

test('restaurar Atrás actualiza controles sin generar otra entrada del historial', () => {
    const f = fixture(['padel']);
    f.binding.set(sportsFromUrl(origin + '/?sports[]=tenis', available));
    assert.deepEqual(f.binding.get(), ['tenis']);
    assert.equal(f.options.find((input) => input.value === 'padel').checked, false);
    assert.deepEqual(f.changes, []);
});

test('el estado entregado a una consulta no cambia al seleccionar otro deporte', () => {
    const f = fixture(['tenis']); const firstRequest = f.binding.get();
    f.toggle('padel'); firstRequest.push('inexistente');
    assert.deepEqual(f.binding.get(), ['padel', 'tenis']);
    assert.deepEqual(f.changes, [['padel', 'tenis']]);
});

test('la inicialización repetida conserva una sola vinculación por grupo', () => {
    const f = fixture();
    assert.equal(bindSportSelection(f.group), f.binding);
    f.toggle('tenis'); assert.deepEqual(f.changes, [['tenis']]);
    assert.equal(bindSportSelection(null), null);
});


test('sin selección se muestra la ayuda y marcar un deporte la oculta', () => {
    const f = fixture();
    assert.equal(f.guide.hidden, false);
    assert.equal(f.clearButton.hidden, true);
    assert.equal(f.caption.hidden, true);
    f.toggle('padel');
    assert.equal(f.guide.hidden, true);
    assert.equal(f.clearButton.hidden, false);
    assert.equal(f.caption.textContent, '1 deporte seleccionado');
    f.clear();
    assert.equal(f.guide.hidden, false);
    assert.equal(f.caption.hidden, true);
});

test('un catálogo sin deportes no pide seleccionar un botón inexistente', () => {
    const f = fixture([], []);
    assert.equal(f.guide.hidden, true);
    assert.equal(f.clearButton.hidden, true);
    assert.deepEqual(f.binding.get(), []);
});
