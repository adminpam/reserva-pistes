/* Selección compartida: cero filtros equivale a Todos; varios se combinan con OR.
   El servidor valida los slugs y decide las pistas, los precios y las plazas. */
export function canonicalSports(values, available) {
    const allowed = new Set(available);
    return [...new Set(values.filter((value) => typeof value === 'string' && allowed.has(value)))].sort();
}

export function sportsFromUrl(value, available) {
    const url = new URL(value);
    const modern = [...url.searchParams].filter(([key]) => /^sports\[(?:\d*)\]$/.test(key)).map(([, slug]) => slug);
    return canonicalSports(modern.length ? modern : [url.searchParams.get('sport')], available);
}

export function withSports(value, selection) {
    const url = new URL(value);
    for (const key of [...url.searchParams.keys()]) {
        if (key === 'sport' || key === 'sports' || key.startsWith('sports[')) url.searchParams.delete(key);
    }
    for (const slug of [...new Set(selection)].sort()) url.searchParams.append('sports[]', slug);
    return url;
}

const bindings = new WeakMap();
export function bindSportSelection(group, onChange = () => {}) {
    if (!group) return null;
    if (bindings.has(group)) return bindings.get(group);
    const options = [...group.querySelectorAll('[data-sport-option]')];
    const available = options.map((input) => input.value);
    const all = group.querySelector('[data-sport-all]');
    const summary = group.querySelector('[data-sport-summary]');
    let selection = [];
    function set(values, notify = false) {
        const next = canonicalSports(values, available);
        const changed = next.join('|') !== selection.join('|');
        selection = next;
        options.forEach((input) => { input.checked = selection.includes(input.value); });
        if (all) { all.hidden = false; all.setAttribute('aria-pressed', String(!selection.length)); }
        if (summary) summary.textContent = !selection.length ? 'Todos los deportes' : selection.length === 1
            ? '1 deporte seleccionado' : `${selection.length} deportes seleccionados`;
        if (notify && changed) onChange([...selection]);
    }
    const changed = (event) => {
        if (options.includes(event.target)) set(options.filter((input) => input.checked).map((input) => input.value), true);
    };
    const clear = () => set([], true);
    group.addEventListener('change', changed);
    all?.addEventListener('click', clear);
    set(options.filter((input) => input.checked).map((input) => input.value));
    const binding = { get: () => [...selection], set, available: [...available] };
    bindings.set(group, binding);
    return binding;
}

// En partidos, los checkboxes son un formulario GET normal; Filtrar confirma.
// El mapa instala su propia llamada y actualiza sin recargar la página.
function init() {
    document.querySelectorAll('[data-sport-filter="submit"]').forEach((group) => bindSportSelection(group));
}
if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
    else init();
}
