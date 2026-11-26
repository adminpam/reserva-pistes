import test from 'node:test';
import assert from 'node:assert/strict';
import { bindAvatarForm, prepareAvatar } from '../../public/ui/profile.js';

const deferred = () => { let resolve, reject; const promise = new Promise((ok, fail) => { resolve = ok; reject = fail; }); return { promise, resolve, reject }; };
function surface(prepare) {
    const listeners = new Map();
    const element = () => ({ hidden: false, disabled: false, dataset: {}, textContent: '', addEventListener: (name, fn) => listeners.set(name, fn), removeEventListener: (name) => listeners.delete(name), removeAttribute(name) { delete this[name]; } });
    const input = element(), submit = element(), status = element(), current = element(), preview = element();
    const form = element();
    form.querySelector = (selector) => ({ '[data-avatar-input]': input, '[data-avatar-submit]': submit, '[data-avatar-status]': status })[selector];
    const root = { querySelector: (selector) => ({ '[data-avatar-current]': current, '[data-avatar-preview]': preview })[selector] };
    const revoked = [];
    const binding = bindAvatarForm(form, { root, prepare, urls: { createObjectURL: (blob) => 'blob:'+blob.id, revokeObjectURL: (url) => revoked.push(url) } });
    return { input, submit, status, current, preview, revoked, listeners, binding };
}

test('la última selección gana aunque termine antes que la anterior', async () => {
    const first = deferred(), second = deferred();
    const s = surface((file) => file.id === 1 ? first.promise : second.promise);
    s.input.files = [{ id: 1 }]; const pendingFirst = s.binding.select();
    s.input.files = [{ id: 2 }]; const pendingSecond = s.binding.select();
    second.resolve({ id: 2, type: 'image/jpeg' }); await pendingSecond;
    first.resolve({ id: 1, type: 'image/jpeg' }); await pendingFirst;
    assert.equal(s.preview.src, 'blob:2');
    assert.equal(s.submit.disabled, false);
    const values = []; s.listeners.get('formdata')({ formData: { set: (...args) => values.push(args) } });
    assert.equal(values[0][1].id, 2);
});

test('no se envía el formulario mientras la foto se prepara', async () => {
    const pending = deferred(); const s = surface(() => pending.promise);
    s.input.files = [{}]; const preparation = s.binding.select();
    let prevented = false; s.listeners.get('submit')({ preventDefault: () => { prevented = true; } });
    assert.equal(prevented, true); assert.equal(s.submit.disabled, true);
    pending.resolve({ id: 1, type: 'image/jpeg' }); await preparation;
    prevented = false; s.listeners.get('submit')({ preventDefault: () => { prevented = true; } });
    assert.equal(prevented, false);
});

test('un error de imagen mantiene la foto actual y bloquea el envío', async () => {
    const s = surface(async () => { throw new Error('Imagen inválida'); });
    s.input.files = [{}]; await s.binding.select();
    assert.equal(s.current.hidden, false); assert.equal(s.preview.hidden, true);
    assert.equal(s.submit.disabled, true); assert.equal(s.status.textContent, 'Imagen inválida');
    let prevented = false; s.listeners.get('submit')({ preventDefault: () => { prevented = true; } });
    assert.equal(prevented, true);
});

test('limpiar o cambiar la selección libera las URLs y descarta resultados atrasados', async () => {
    const pending = deferred(); let count = 0;
    const s = surface(() => ++count === 1 ? Promise.resolve({ id: 1, type: 'image/jpeg' }) : pending.promise);
    s.input.files = [{}]; await s.binding.select();
    s.input.files = [{}]; const preparation = s.binding.select();
    assert.deepEqual(s.revoked, ['blob:1']);
    s.input.files = []; await s.binding.select();
    pending.resolve({ id: 2, type: 'image/jpeg' }); await preparation;
    assert.equal(s.preview.hidden, true); assert.equal(s.current.hidden, false);
    const values = []; s.listeners.get('formdata')({ formData: { set: (...args) => values.push(args) } });
    assert.equal(values.length, 0);
    s.binding.destroy(); assert.equal(s.listeners.has('formdata'), false);
});

test('rechaza SVG y archivos de más de 10 MB antes de decodificarlos', async () => {
    let calls = 0; const options = { loadImage: async () => { calls++; } };
    await assert.rejects(prepareAvatar({ type: 'image/svg+xml', size: 100 }, options), /JPG, PNG o WebP/);
    await assert.rejects(prepareAvatar({ type: 'image/png', size: 11 * 1024 * 1024 }, options), /10 MB/);
    assert.equal(calls, 0);
});

test('una imagen pequeña se rechaza y libera su recurso', async () => {
    let cleaned = false;
    await assert.rejects(prepareAvatar({ type: 'image/png', size: 100 }, {
        loadImage: async () => ({ width: 16, height: 16, cleanup: () => { cleaned = true; } }),
    }), /demasiado pequeña/);
    assert.equal(cleaned, true);
});

test('prepara una miniatura centrada de 384 píxeles y libera la imagen', async () => {
    let cleaned = false, drawArgs;
    const blob = new Blob(['imagen ficticia'], { type: 'image/jpeg' });
    const canvas = { getContext: () => ({ fillRect() {}, drawImage(...args) { drawArgs = args; } }), toBlob: (callback) => callback(blob) };
    const result = await prepareAvatar({ type: 'image/png', size: 100 }, {
        loadImage: async () => ({ image: 'source', width: 1200, height: 1800, cleanup: () => { cleaned = true; } }), makeCanvas: () => canvas,
    });
    assert.equal(result, blob); assert.equal(canvas.width, 384); assert.equal(canvas.height, 384);
    assert.deepEqual(drawArgs, ['source', 0, 300, 1200, 1200, 0, 0, 384, 384]);
    assert.equal(cleaned, true);
});

test('un fallo al generar la miniatura libera la imagen y no devuelve un archivo', async () => {
    let cleaned = false;
    await assert.rejects(prepareAvatar({ type: 'image/png', size: 100 }, {
        loadImage: async () => ({ image: 'source', width: 64, height: 64, cleanup: () => { cleaned = true; } }),
        makeCanvas: () => ({ getContext: () => ({ fillRect() {}, drawImage() {} }), toBlob: (callback) => callback(null) }),
    }), /No se ha podido reducir/);
    assert.equal(cleaned, true);
});


test('si el codificador no termina, libera la imagen y permite informar del error', async () => {
    let cleaned = false;
    await assert.rejects(prepareAvatar({ type: 'image/png', size: 100 }, {
        loadImage: async () => ({ image: 'source', width: 64, height: 64, cleanup: () => { cleaned = true; } }),
        makeCanvas: () => ({ getContext: () => ({ fillRect() {}, drawImage() {} }), toBlob() {} }),
        encodeTimeoutMs: 1,
    }), /tarda demasiado/);
    assert.equal(cleaned, true);
});
