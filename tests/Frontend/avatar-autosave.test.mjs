import test from 'node:test';
import assert from 'node:assert/strict';
import { createAvatarAutosave, normalizeAvatar, createAvatarRequest, applyConfirmedAvatar, bindAvatarAutosave } from '../../public/ui/avatar-autosave.js';

const origin = 'http://localhost:8000';
const endpoint = '/users/7/avatar';
const a = 'a'.repeat(64), b = 'b'.repeat(64), c = 'c'.repeat(64);
const payload = (version, initials = 'PD') => ({ message: 'Foto guardada.', avatar: { url: version ? `${origin}${endpoint}?v=${version}` : null, version, initials } });
const decode = (data) => normalizeAvatar(data, { origin, avatarEndpoint: endpoint });
const deferred = () => { let resolve, reject; const promise = new Promise((ok, fail) => { resolve = ok; reject = fail; }); return { promise, resolve, reject }; };
const tick = () => new Promise((resolve) => setImmediate(resolve));
const blob = (text = 'foto de prueba') => new Blob([text], { type: 'image/jpeg' });

function clock() {
    let id = 0; const tasks = new Map();
    return { tasks, set(fn, ms) { tasks.set(++id, { fn, ms }); return id; }, clear(id) { tasks.delete(id); },
        fire(ms) { const entry = [...tasks].find(([, task]) => task.ms === ms); assert.ok(entry); tasks.delete(entry[0]); entry[1].fn(); } };
}

function machine(t, options = {}) {
    const calls = [], states = [], confirmed = [], timers = clock();
    const instance = createAvatarAutosave({ initial: decode(payload(null)), decode, prepare: async (file) => file.blob ?? blob(file.id),
        send(file, version, signal) { const response = deferred(); calls.push({ file, version, signal, ...response }); return response.promise; },
        onState: (state) => states.push(state), onConfirmed: (value) => confirmed.push(value), timers, ...options });
    t.after(() => instance.destroy());
    return { instance, calls, states, confirmed, timers,
        reply(index, status = 200, data = payload(a)) { calls[index].resolve({ status, payload: data }); } };
}

test('los metadatos solo admiten la URL local del usuario y una versión coherente', () => {
    assert.equal(decode(payload(a)).url, `${origin}${endpoint}?v=${a}`);
    assert.deepEqual(decode(payload(null)), { url: null, version: null, initials: 'PD' });
    for (const url of [`https://example.org${endpoint}?v=${a}`, `${origin}/users/9/avatar?v=${a}`,
        `${origin}${endpoint}?v=${b}`, 'javascript:alert(1)', `${origin}${endpoint}?v=${a}&v=${b}`]) {
        assert.throws(() => decode({ avatar: { url, version: a, initials: 'PD' } }));
    }
    assert.throws(() => decode({ avatar: { url: null, version: a, initials: 'PD' } }));
});

test('elegir una imagen inicia el guardado sin enviar el formulario manualmente', async (t) => {
    const m = machine(t); await m.instance.select({ id: 'A' }); await tick();
    assert.equal(m.calls.length, 1); assert.equal(m.calls[0].version, null);
    assert.equal(m.instance.snapshot().phase, 'saving'); assert.equal(m.confirmed.length, 0);
    m.reply(0); await tick();
    assert.equal(m.instance.snapshot().phase, 'saved'); assert.equal(m.instance.snapshot().preview, null);
    assert.equal(m.instance.snapshot().confirmed.version, a);
    assert.equal(m.timers.tasks.size, 0);
});

test('una preparación vieja no se envía aunque termine después de la última', async (t) => {
    const first = deferred(), second = deferred();
    const m = machine(t, { prepare: (file) => file.id === 'A' ? first.promise : second.promise });
    const p1 = m.instance.select({ id: 'A' }), p2 = m.instance.select({ id: 'B' });
    second.resolve(blob('B')); await p2; await tick();
    first.resolve(blob('A')); await p1; await tick();
    assert.equal(m.calls.length, 1); assert.equal(await m.calls[0].file.text(), 'B');
    m.reply(0, 200, payload(b)); await tick();
    assert.equal(m.instance.snapshot().confirmed.version, b);
});

test('las subidas se serializan y la siguiente usa la versión que acaba de confirmarse', async (t) => {
    const m = machine(t); await m.instance.select({ id: 'A' }); await tick();
    await m.instance.select({ id: 'B' }); await tick();
    assert.equal(m.calls.length, 1); assert.equal(m.calls[0].signal.aborted, false);
    m.reply(0); await tick();
    assert.equal(m.calls.length, 2); assert.equal(m.calls[1].version, a);
    assert.equal(await m.calls[1].file.text(), 'B');
    assert.equal(m.instance.snapshot().phase, 'saving');
    m.reply(1, 200, payload(b)); await tick();
    assert.equal(m.instance.snapshot().confirmed.version, b);
});

test('si cambian la elección dos veces durante una subida, solo queda pendiente la última', async (t) => {
    const m = machine(t); await m.instance.select({ id: 'A' }); await tick();
    await m.instance.select({ id: 'B' }); await m.instance.select({ id: 'C' }); await tick();
    m.reply(0); await tick();
    assert.equal(m.calls.length, 2); assert.equal(await m.calls[1].file.text(), 'C');
    m.reply(1, 200, payload(c)); await tick();
    assert.equal(m.instance.snapshot().confirmed.version, c);
});

test('un conflicto se reintenta una sola vez con la versión actual del servidor', async (t) => {
    const m = machine(t); await m.instance.select({ id: 'B' }); await tick();
    m.reply(0, 409, payload(a)); await tick();
    assert.equal(m.calls.length, 2); assert.equal(m.calls[1].version, a);
    m.reply(1, 409, payload(b)); await tick();
    assert.equal(m.calls.length, 2); assert.equal(m.instance.snapshot().phase, 'error');
    assert.equal(m.instance.snapshot().confirmed.version, b);
});

test('una selección sustituida no se reintenta después de un conflicto', async (t) => {
    const m = machine(t); await m.instance.select({ id: 'A' }); await tick();
    await m.instance.select({ id: 'B' }); await tick();
    m.reply(0, 409, payload(c)); await tick();
    assert.equal(await m.calls[1].file.text(), 'B'); assert.equal(m.calls[1].version, c);
    m.reply(1, 200, payload(b)); await tick();
});

test('cancelar el selector no cambia la foto ni interrumpe una subida', async (t) => {
    const m = machine(t); await m.instance.select({ id: 'A' }); await tick();
    await m.instance.select(null);
    assert.equal(m.calls.length, 1); assert.equal(m.calls[0].signal.aborted, false);
    m.reply(0); await tick(); assert.equal(m.instance.snapshot().phase, 'saved');
});

test('los errores de validación conservan la foto confirmada y permiten otra elección', async (t) => {
    const m = machine(t, { initial: decode(payload(a)) });
    await m.instance.select({ id: 'B' }); await tick();
    m.reply(0, 422, { errors: { avatar: ['Has cambiado la foto muchas veces. Espera un minuto.'] } }); await tick();
    assert.equal(m.instance.snapshot().confirmed.version, a); assert.equal(m.instance.snapshot().preview, null);
    assert.match(m.instance.snapshot().message, /Espera un minuto/);
    await m.instance.select({ id: 'C' }); await tick();
    m.reply(1, 200, payload(c)); await tick(); assert.equal(m.instance.snapshot().confirmed.version, c);
});

test('una sesión caducada informa sin confirmar ni redirigir otros formularios', async (t) => {
    const m = machine(t); await m.instance.select({ id: 'A' }); await tick();
    m.reply(0, 419, null); await tick();
    assert.equal(m.instance.snapshot().phase, 'error'); assert.match(m.instance.snapshot().message, /sesión ha caducado/);
    assert.equal(m.confirmed.length, 0);
});

test('un error de preparación mantiene la foto guardada y no envía ninguna petición', async (t) => {
    const m = machine(t, { initial: decode(payload(a)), prepare: async () => { throw new Error('Imagen no válida.'); } });
    await m.instance.select({ id: 'B' }); await tick();
    assert.equal(m.calls.length, 0); assert.equal(m.instance.snapshot().confirmed.version, a);
    assert.equal(m.instance.snapshot().phase, 'error');
});

test('el tiempo de espera termina y una respuesta tardía no sustituye la confirmada', async (t) => {
    const m = machine(t, { initial: decode(payload(a)) }); await m.instance.select({ id: 'B' }); await tick();
    m.timers.fire(20000); await tick();
    assert.equal(m.calls[0].signal.aborted, true); assert.equal(m.instance.snapshot().phase, 'error');
    m.reply(0, 200, payload(b)); await tick();
    assert.equal(m.instance.snapshot().confirmed.version, a); assert.equal(m.timers.tasks.size, 0);
});

test('la preparación tampoco puede dejar el círculo esperando indefinidamente', async (t) => {
    const pending = deferred(), m = machine(t, { prepare: () => pending.promise });
    const selection = m.instance.select({ id: 'A' }); await tick();
    m.timers.fire(35000); await selection;
    assert.equal(m.instance.snapshot().phase, 'error'); assert.equal(m.calls.length, 0);
    pending.resolve(blob()); await tick(); assert.equal(m.calls.length, 0);
});

test('una respuesta de éxito con una URL ajena no se aplica como avatar', async (t) => {
    const m = machine(t); await m.instance.select({ id: 'A' }); await tick();
    m.reply(0, 200, { avatar: { ...payload(a).avatar, url: `https://example.org${endpoint}?v=${a}` } }); await tick();
    assert.equal(m.instance.snapshot().phase, 'error'); assert.equal(m.confirmed.length, 0);
});

test('abandonar la página cancela tareas y descarta cualquier resultado posterior', async (t) => {
    const m = machine(t); await m.instance.select({ id: 'A' }); await tick();
    const count = m.states.length; m.instance.destroy(); await tick();
    assert.equal(m.calls[0].signal.aborted, true); assert.equal(m.timers.tasks.size, 0);
    m.reply(0); await tick(); assert.equal(m.states.length, count); assert.equal(m.confirmed.length, 0);
});

test('el transporte envía multipart, CSRF y versión sin fijar Content-Type ni salir del origen', async () => {
    let request;
    const send = createAvatarRequest({ action: `${origin}/profile/avatar`, csrf: 'token-de-prueba', origin,
        fetchImpl: async (url, options) => { request = { url, ...options }; return { status: 200, json: async () => payload(a) }; } });
    await send(blob(), null, new AbortController().signal);
    assert.equal(request.method, 'POST'); assert.equal(request.credentials, 'same-origin'); assert.equal(request.redirect, 'error');
    assert.equal(request.headers.Accept, 'application/json'); assert.equal(request.headers['Content-Type'], undefined);
    assert.equal(request.headers['X-CSRF-TOKEN'], 'token-de-prueba');
    assert.equal(request.body.get('_token'), 'token-de-prueba'); assert.equal(request.body.get('expected_avatar_version'), '');
    assert.equal(request.body.get('avatar').name, 'avatar.jpg');
    assert.throws(() => createAvatarRequest({ action: 'https://example.org/profile/avatar', csrf: 'token', origin }));
});

function surface() {
    function node(tag = 'span') {
        return { tag, dataset: {}, children: [], attrs: {}, hidden: false, value: '', listeners: new Map(),
            addEventListener(name, fn) { this.listeners.set(name, fn); }, removeEventListener(name) { this.listeners.delete(name); },
            setAttribute(name, value) { this.attrs[name] = value; }, removeAttribute(name) { delete this[name]; },
            replaceChildren(...children) { this.children = children; }, click() { this.clicks = (this.clicks ?? 0) + 1; },
        };
    }
    const photo = () => Object.assign(node(), { dataset: { userAvatar: '7', avatarUrl: payload(a).avatar.url, avatarVersion: a, avatarInitials: 'PD' } });
    const current = photo(), header = photo(), other = photo(); other.dataset.userAvatar = '8';
    const input = node('input'), pick = node('button'), status = node('p'), preview = node('img'), version = node('input'), csrf = node('input');
    version.value = a; csrf.value = 'csrf-ficticio'; preview.hidden = true;
    const root = { defaultView: { location: { origin } }, querySelectorAll: () => [current, header, other], createElement: (tag) => node(tag) };
    for (const target of [current, header, other]) target.ownerDocument = root;
    const form = node('form'); form.ownerDocument = root; form.action = `${origin}/profile/avatar`;
    form.dataset = { avatarUser: '7', avatarEndpoint: endpoint };
    const fields = { '[data-avatar-input]': input, '[data-avatar-pick]': pick, '[data-avatar-status]': status,
        '[data-avatar-preview]': preview, '[data-user-avatar]': current,
        '[name="expected_avatar_version"]': version, '[name="_token"]': csrf };
    form.querySelector = (selector) => fields[selector];
    return { root, form, current, header, other, input, pick, status, preview, version };
}

test('el círculo abre el selector y confirma foto en perfil/cabecera sin tocar otros usuarios', async (t) => {
    const s = surface(), response = deferred(), revoked = [];
    const binding = bindAvatarAutosave(s.form, { prepare: async () => blob(),
        urls: { createObjectURL: () => 'blob:preview', revokeObjectURL: (url) => revoked.push(url) },
        fetchImpl: () => response.promise });
    t.after(() => binding.destroy());
    s.pick.listeners.get('click')(); assert.equal(s.input.clicks, 1);
    s.input.files = [{}]; s.input.listeners.get('change')(); await tick();
    assert.equal(s.preview.hidden, false); assert.equal(s.header.dataset.avatarVersion, a);
    assert.equal(s.form.attrs['aria-busy'], 'true');
    response.resolve({ status: 200, json: async () => payload(b) }); await tick();
    assert.equal(s.current.dataset.avatarVersion, b); assert.equal(s.header.dataset.avatarVersion, b);
    assert.equal(s.other.dataset.avatarVersion, a); assert.equal(s.version.value, b);
    assert.equal(s.preview.hidden, true); assert.equal(s.current.hidden, false);
    assert.deepEqual(revoked, ['blob:preview']); assert.equal(s.form.attrs['aria-busy'], 'false');
    assert.equal(s.status.textContent, 'Foto guardada.');
});

test('volver a mejorar el formulario no duplica botones ni envíos y bloquea el POST nativo', (t) => {
    const s = surface(), binding = bindAvatarAutosave(s.form); t.after(() => binding.destroy());
    assert.equal(bindAvatarAutosave(s.form), binding);
    let prevented = false; s.form.listeners.get('submit')({ preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    binding.destroy(); assert.equal(s.input.listeners.has('change'), false); assert.equal(s.pick.disabled, true);
});

test('las iniciales se aplican como texto y solo al usuario correspondiente', () => {
    const s = surface();
    applyConfirmedAvatar(s.root, '7', { url: null, version: null, initials: '<>' });
    assert.equal(s.header.textContent, '<>'); assert.equal(s.header.dataset.avatarVersion, '');
    assert.equal(s.other.dataset.avatarVersion, a);
});

test('un fallo de lectura de una foto antigua no borra la imagen nueva de la cabecera', () => {
    const s = surface();
    applyConfirmedAvatar(s.root, '7', decode(payload(a)));
    const old = s.header.children[0];
    applyConfirmedAvatar(s.root, '7', decode(payload(b)));
    const current = s.header.children[0]; s.header.firstElementChild = current;
    old.listeners.get('error')();
    assert.equal(s.header.children[0], current); assert.equal(s.header.textContent, undefined);
    current.listeners.get('error')();
    assert.equal(s.header.textContent, 'PD');
});
