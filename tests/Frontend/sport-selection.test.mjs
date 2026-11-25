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

test('restaura enlaces antiguos y representa Todos sin filtros', () => {
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

test('Todos elimina cada parámetro de deporte y deja los otros filtros', () => {
    const next = withSports(origin + '/matches?sports[0]=tenis&sports[]=padel&club=Demo&my_level=1', []);
    assert.equal(next.searchParams.get('club'), 'Demo');
    assert.equal(next.searchParams.get('my_level'), '1');
    assert.deepEqual([...next.searchParams.keys()], ['club', 'my_level']);
});

function fixture(initial = []) {
    const options = available.map((value) => ({ value, checked: initial.includes(value) }));
    const events = new Map();
    const allEvents = new Map();
    const attrs = {};
    const all = { hidden: true, setAttribute: (key, value) => { attrs[key] = value; }, addEventListener: (key, fn) => allEvents.set(key, fn) };
    const summary = { textContent: '' };
    const group = {
        querySelectorAll: () => options,
        querySelector: (selector) => selector === '[data-sport-all]' ? all : summary,
        addEventListener: (key, fn) => events.set(key, fn),
    };
    const changes = [];
    const binding = bindSportSelection(group, (value) => changes.push(value));
    return { options, group, all, attrs, summary, changes, binding,
        toggle(value) { const input = options.find((option) => option.value === value); input.checked = !input.checked; events.get('change')({ target: input }); },
        clear() { allEvents.get('click')(); } };
}

test('añadir el segundo deporte conserva el primero y comunica ambas opciones', () => {
    const f = fixture();
    f.toggle('tenis'); f.toggle('padel');
    assert.deepEqual(f.binding.get(), ['padel', 'tenis']);
    assert.equal(f.options.find((input) => input.value === 'tenis').checked, true);
    assert.equal(f.attrs['aria-pressed'], 'false');
    assert.equal(f.summary.textContent, '2 deportes seleccionados');
    assert.deepEqual(f.changes, [['tenis'], ['padel', 'tenis']]);
});

test('desmarcar el último vuelve a Todos sin bloquear el formulario', () => {
    const f = fixture(['tenis']); f.toggle('tenis');
    assert.deepEqual(f.binding.get(), []);
    assert.equal(f.attrs['aria-pressed'], 'true');
    assert.equal(f.summary.textContent, 'Todos los deportes');
});

test('Todos desmarca las opciones y repetirlo no duplica consultas', () => {
    const f = fixture(['tenis', 'padel']); f.clear(); f.clear();
    assert.deepEqual(f.changes, [[]]);
    assert.equal(f.options.some((input) => input.checked), false);
    assert.equal(f.all.hidden, false);
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
