import test from 'node:test';
import assert from 'node:assert/strict';
import {remainingSeconds, demoValues, mountCheckout} from '../../public/ui/checkout.js';
import {renewCheckoutKey, mountCheckoutStart} from '../../public/ui/checkout-start.js';
const initial = '2026-11-24T12:00:00+01:00', expiry = '2026-11-24T12:05:00+01:00';
const uuid = 'ea14b67a-43e9-4127-8aa5-0af345f9bd73';
function surface() {
    const handlers = {}, clock = {ms: 0, now() {return this.ms;}};
    const button = {disabled: false, focus() {this.focused = true;}}, output = {}, message = {hidden: true}, tool = {hidden: true};
    const fields = Object.fromEntries(['holder', 'number', 'expiry', 'cvc'].map(name => [`payment[${name}]`, {value: ''}]));
    const form = {valid: true, elements: {namedItem: name => fields[name]}, checkValidity() {return this.valid;}, addEventListener: (name, fn) => handlers[name] = fn};
    const fill = {dataset: {demoCard: '4242424242424242'}, addEventListener: (name, fn) => handlers.fill = fn};
    const root = {querySelector: selector => ({'[data-payment-form]': form, '[data-checkout-clock]': {dataset: {expires: expiry, serverNow: initial}}, '[data-pay-button]': button, '[data-countdown]': output, '[data-expiry-message]': message})[selector], querySelectorAll: selector => selector === '[data-demo-tools]' ? [tool] : [fill]};
    const binding = mountCheckout(root, clock, () => 7);
    return {handlers, clock, button, output, message, tool, fields, form, binding};
}
const event = () => ({prevented: false, preventDefault() {this.prevented = true;}});

test('el contador usa la hora del servidor y el tiempo transcurrido', () => {
    assert.equal(remainingSeconds(expiry, initial), 300);
    assert.equal(remainingSeconds(expiry, initial, 299001), 1);
    assert.equal(remainingSeconds(expiry, initial, 300000), 0);
    assert.equal(remainingSeconds(expiry, initial, -10000), 300);
});
test('fechas incorrectas o un estado vencido no permiten continuar', () => {
    assert.equal(remainingSeconds('invalid', initial), 0);
    assert.equal(remainingSeconds(initial, expiry), 0);
    assert.equal(remainingSeconds(expiry, initial, NaN), 0);
});
test('las únicas ayudas disponibles rellenan datos completamente ficticios', () => {
    const values = demoValues('4242424242424242', new Date(initial));
    assert.deepEqual(values, {holder: 'Persona Demo', number: '4242424242424242', expiry: '12/28', cvc: '123'});
    assert.equal(demoValues('4000000000000002').number, '4000000000000002');
    assert.equal(demoValues('1111111111111111'), null);
});
test('rellenar una tarjeta no envía el formulario ni crea una reserva', () => {
    const s = surface();
    s.handlers.fill();
    assert.equal(s.fields['payment[number]'].value, '4242424242424242');
    assert.equal(s.fields['payment[holder]'].value, 'Persona Demo');
    assert.equal(s.button.disabled, false);
    assert.equal(s.button.focused, true);
    assert.equal(s.tool.hidden, false);
});
test('al caducar se bloquea el envío y se ofrece actualizar el estado', () => {
    const s = surface();
    s.clock.ms = 300001;
    s.binding.tick();
    assert.equal(s.button.disabled, true);
    assert.equal(s.output.textContent, '0:00');
    assert.equal(s.message.hidden, false);
    const e = event(); s.handlers.submit(e);
    assert.equal(e.prevented, true);
});
test('un segundo envío se bloquea mientras el primer resultado está pendiente', () => {
    const s = surface();
    const first = event(); s.handlers.submit(first);
    const second = event(); s.handlers.submit(second);
    assert.equal(first.prevented, false);
    assert.equal(second.prevented, true);
    assert.equal(s.button.disabled, true);
});
test('la validación nativa no deja bloqueado un formulario incompleto', () => {
    const s = surface(); s.form.valid = false;
    const e = event(); s.handlers.submit(e);
    assert.equal(e.prevented, true);
    assert.equal(s.button.disabled, false);
    s.form.valid = true;
    const retry = event(); s.handlers.submit(retry);
    assert.equal(retry.prevented, false);
});
test('una página sin formulario no inicia temporizadores', () => {
    let scheduled = false;
    assert.equal(mountCheckout({querySelector: () => null}, undefined, () => scheduled = true), null);
    assert.equal(scheduled, false);
});
test('sin generador de UUID se conserva la clave válida preparada por el servidor', () => {
    const field = {value: uuid}, form = {elements: {namedItem: () => field}};
    assert.equal(renewCheckoutKey(form, null), false);
    assert.equal(renewCheckoutKey(form, () => 'wrong'), false);
    assert.equal(field.value, uuid);
});
test('cambiar selección o volver desde caché renueva la clave; repetir el envío no la cambia', () => {
    const handlers = {}, pageHandlers = {}, field = {value: 'old'};
    const form = {elements: {namedItem: () => field}, addEventListener: (name, fn) => handlers[name] = fn};
    mountCheckoutStart({querySelectorAll: () => [form]}, () => uuid, {addEventListener: (name, fn) => pageHandlers[name] = fn});
    handlers.change({target: {name: 'hour'}});
    assert.equal(field.value, uuid);
    field.value = 'unchanged';
    handlers.change({target: {name: 'irrelevant'}});
    assert.equal(field.value, 'unchanged');
    assert.equal(handlers.submit, undefined);
    pageHandlers.pageshow({persisted: false});
    assert.equal(field.value, 'unchanged');
    pageHandlers.pageshow({persisted: true});
    assert.equal(field.value, uuid);
});
