// La confirmación debe bloquear el envío original y conservar el botón elegido.
import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import { readFile } from 'node:fs/promises';

const code = await readFile(new URL('../../public/ui/screens.js', import.meta.url), 'utf8');
function setup() {
    let submitHandler;
    const actions = {};
    const text = { textContent: '' };
    const dialog = {
        querySelector(selector) {
            if (selector === '#rp-confirm-text') return text;
            return { addEventListener(name, fn) { actions[selector] = fn; }, focus() {} };
        },
        showModal() { this.open = true; },
        close() { this.open = false; },
        addEventListener() {},
    };
    class Form {
        dataset = { confirm: '¿Cancelar esta reserva?' };
        requests = 0;
        requestSubmit(button) {
            const event = makeEvent(this, button); submitHandler(event);
            if (!event.prevented) this.requests++;
        }
    }
    function makeEvent(form, button = {}) {
        return { target: form, submitter: button, prevented: false, stopped: false,
            preventDefault() { this.prevented = true; }, stopImmediatePropagation() { this.stopped = true; } };
    }
    const doc = { getElementById: () => dialog, querySelectorAll: () => [],
        addEventListener(name, fn, capture) { assert.equal(capture, true); submitHandler = fn; } };
    vm.runInNewContext(code, { document: doc, HTMLFormElement: Form, window: {} });
    const form = new Form();
    return { dialog, text, form, event: () => { const event = makeEvent(form); submitHandler(event); return event; },
        cancel: () => actions['[data-confirm-cancel]'](), accept: () => actions['[data-confirm-accept]']() };
}
test('pedir confirmación no envía el formulario ni bloquea su botón', () => {
    const s = setup(), event = s.event();
    assert.equal(event.prevented, true); assert.equal(event.stopped, true);
    assert.equal(s.form.requests, 0); assert.equal(s.dialog.open, true);
    assert.equal(s.text.textContent, '¿Cancelar esta reserva?');
});
test('cancelar no envía ninguna petición', () => {
    const s = setup(); s.event(); s.cancel();
    assert.equal(s.form.requests, 0); assert.equal(s.dialog.open, false);
});
test('aceptar reanuda el envío una sola vez', () => {
    const s = setup(); s.event(); s.accept(); s.accept();
    assert.equal(s.form.requests, 1); assert.equal(s.dialog.open, false);
});
