/* Ayuda progresiva: el servidor conserva la decisión y los formularios normales.
   No guarda valores, no envía peticiones ni sustituye las respuestas HTTP. */
const mounted = new WeakMap();
const controls = form => [...form.elements].filter(field => field.name && /^(INPUT|SELECT|TEXTAREA)$/.test(field.tagName));
const candidates = form => controls(form).filter(field => field.willValidate && !field.closest('[hidden]'));
const named = (form, name) => controls(form).find(field => field.name === name);
const text = field => String(field?.value ?? '');
const dotted = name => name.replace(/\[([^\]]*)\]/g, '.$1').replace(/\.$/, '');
const labels = {email:'correo electrónico', name:'nombre', password:'contraseña', password_confirmation:'confirmación de contraseña', current_password:'contraseña actual', price_euros:'precio por hora', open_hour:'hora de apertura', close_hour:'hora de cierre', min_level:'nivel mínimo', max_level:'nivel máximo', date:'fecha', hour:'hora', sport_id:'deporte', club_id:'club', q:'búsqueda', club:'club', 'payment[holder]':'titular de prueba', 'payment[number]':'número de tarjeta de prueba', 'payment[expiry]':'caducidad de prueba', 'payment[cvc]':'código de prueba'};
const label = field => labels[field.name] || field.getAttribute('aria-label') || field.labels?.[0]?.textContent?.trim().toLocaleLowerCase('es') || 'dato';

export function formIdentity(form) {
    // Solo ruta, método y pista: nunca contraseñas, tarjetas ni tokens CSRF.
    const method = text(named(form, '_method')) || form.method || 'get';
    return JSON.stringify([new URL(form.action, form.ownerDocument.baseURI).pathname, method.toUpperCase(), text(named(form, 'court_id'))]);
}

export function fieldMessage(field, form) {
    const value = text(field), validity = field.validity || {}, title = label(field);
    if (validity.badInput) return `Introduce un número válido en ${title}.`;
    if (validity.valueMissing || (field.required && !['radio','checkbox'].includes(field.type) && !value.trim())) {
        return field.name === 'hour' ? 'Elige una hora disponible.' : field.type === 'radio' ? `Elige una opción para ${title}.` : `Completa el campo ${title}.`;
    }
    if (validity.typeMismatch) return field.type === 'email' ? 'Introduce un correo electrónico válido.' : `Revisa el formato de ${title}.`;
    if (validity.rangeUnderflow) return `El campo ${title} debe ser como mínimo ${field.min}.`;
    if (validity.rangeOverflow) return `El campo ${title} no puede superar ${field.max}.`;
    if (validity.stepMismatch) return field.name === 'price_euros' ? 'El precio admite como máximo dos decimales.' : `Introduce un valor válido en ${title}.`;
    if (validity.patternMismatch) return field.name === 'payment[expiry]' ? 'La caducidad debe tener formato MM/AA.' : field.name === 'payment[cvc]' ? 'El código de prueba es 123.' : `Revisa el formato de ${title}.`;
    const size = [...value].length;
    if (value && field.minLength > 0 && size < field.minLength) return `El campo ${title} debe tener al menos ${field.minLength} caracteres.`;
    if (field.maxLength >= 0 && size > field.maxLength) return `El campo ${title} no puede superar ${field.maxLength} caracteres.`;
    const newPassword = named(form, 'password_confirmation');
    if (value && newPassword && ['password','password_confirmation'].includes(field.name)) {
        if (size < 8) return 'La contraseña debe tener al menos 8 caracteres.';
        if (new TextEncoder().encode(value).length > 72) return 'La contraseña es demasiado larga. Usa una más corta.';
        if (value.includes('\0')) return 'La contraseña contiene caracteres no admitidos.';
        if (field.name === 'password_confirmation' && value !== text(named(form, 'password'))) return 'Las contraseñas no coinciden. Repite la misma contraseña en ambos campos.';
    }
    if (value && field.name === 'close_hour') {
        const opening = text(named(form, 'open_hour'));
        if (opening !== '' && Number(value) <= Number(opening)) return 'La hora de cierre debe ser posterior a la hora de apertura.';
    }
    if (value && field.name === 'max_level' && named(form, 'is_open')?.checked) {
        const minimum = text(named(form, 'min_level'));
        if (minimum !== '' && Number(value) < Number(minimum)) return 'El nivel máximo debe ser igual o superior al mínimo.';
    }
    if (form.hasAttribute('data-payment-form') && value) {
        if (field.name === 'payment[holder]' && value !== 'Persona Demo') return 'Usa únicamente el titular ficticio Persona Demo.';
        if (field.name === 'payment[number]' && !['4242424242424242','4000000000000002'].includes(value.replace(/[\s-]/g, ''))) return 'Usa una de las dos tarjetas ficticias indicadas. No introduzcas una tarjeta real.';
        if (field.name === 'payment[cvc]' && value !== '123') return 'El código de prueba es 123.';
    }
    if (validity.customError || validity.tooLong || validity.tooShort || validity.valid === false) return `Revisa el campo ${title}.`;
    return '';
}

export function collectIssues(form) {
    const errors = new Map();
    for (const field of candidates(form)) {
        const message = fieldMessage(field, form);
        if (message && !errors.has(field.name)) errors.set(field.name, message);
    }
    return errors;
}

export function fieldsForError(form, key) {
    const available = candidates(form);
    const exact = available.filter(field => dotted(field.name) === key);
    if (exact.length) return exact;
    const indexed = available.filter(field => field.name.endsWith('[]') && key.startsWith(dotted(field.name) + '.'));
    if (indexed.length) return indexed;
    // Un fallo del array completo se relaciona con su grupo visible.
    return available.filter(field => dotted(field.name).startsWith(key + '.') || field.name === key + '[]');
}

export function chooseServerForm(forms, errors, identity) {
    if (typeof identity === 'string' && identity) return forms.find(form => formIdentity(form) === identity) || null;
    // Sin contexto no se atribuye un error a una fila arbitraria.
    const matches = forms.filter(form => Object.keys(errors).some(key => fieldsForError(form, key).length));
    return matches.length === 1 ? matches[0] : null;
}

export function enhanceValidation(doc = document) {
    if (mounted.has(doc)) return mounted.get(doc);
    const states = new Map(); let serial = 0;
    const forms = [...doc.forms].filter(form => !form.hasAttribute('data-avatar-autosave') && !form.noValidate && !new URL(form.action,doc.baseURI).pathname.endsWith('/logout'));
    const focus = node => {
        if (!node) return;
        node.focus({preventScroll:true});
        (node.closest('.rp-field,.field,fieldset,[role="group"]') || node).scrollIntoView?.({block:'center', behavior:'auto'});
    };
    for (const form of forms) {
        const summary = doc.createElement('div'); summary.className = 'rp-validation-summary'; summary.hidden = true;
        summary.setAttribute('role','alert'); summary.setAttribute('tabindex','-1'); form.prepend(summary);
        states.set(form, {summary, server:new Map(), client:new Map(), nodes:[], original:new Map(), touched:new Set(), attempted:false});
        if (form.method.toLowerCase() !== 'get') {
            const marker = doc.createElement('input'); marker.type = 'hidden'; marker.name = '_validation_form'; marker.value = formIdentity(form); form.append(marker);
        }
    }
    const paint = form => {
        const state = states.get(form);
        const messages = new Map([...state.server, ...state.client]);
        const signature = JSON.stringify([...messages]);
        // No reconstruir regiones de aviso ni descripciones en cada pulsación.
        if (state.signature === signature) return state.first;
        state.signature = signature;
        for (const [field, attributes] of state.original) {
            for (const [key, value] of Object.entries(attributes)) value === null ? field.removeAttribute(key) : field.setAttribute(key, value);
        }
        state.original.clear(); state.nodes.forEach(node => node.remove()); state.nodes = [];
        let first = null;
        for (const [key, message] of messages) {
            const fields = fieldsForError(form, dotted(key));
            if (!fields.length) continue;
            const output = doc.createElement('p'); output.className = 'rp-field-error'; output.id = `rp-error-${++serial}`; output.textContent = message;
            const anchor = fields[0].closest('.rp-field,.field,fieldset,[role="group"],.rp-password-wrap,.field-icon') || fields[0];
            if (anchor.matches('.rp-field,.field,fieldset')) anchor.append(output); else anchor.after(output);
            state.nodes.push(output);
            for (const field of fields) {
                if (!state.original.has(field)) state.original.set(field, {'aria-invalid':field.getAttribute('aria-invalid'), 'aria-describedby':field.getAttribute('aria-describedby')});
                field.setAttribute('aria-invalid','true');
                field.setAttribute('aria-describedby', [...new Set(((field.getAttribute('aria-describedby') || '') + ' ' + output.id).trim().split(/\s+/))].join(' '));
            }
            first ||= fields[0];
        }
        state.summary.replaceChildren(); state.summary.hidden = messages.size === 0;
        if (messages.size) {
            const title = doc.createElement('strong'); title.textContent = 'Revisa los datos indicados'; state.summary.append(title);
            const note = doc.createElement('p'); note.textContent = messages.size === 1 ? 'Hay un dato que necesita tu atención.' : `Hay ${messages.size} datos que necesitan tu atención.`; state.summary.append(note);
            // Los fallos de campos ocultos o de negocio conservan su texto completo.
            for (const [key, message] of messages) if (!fieldsForError(form, dotted(key)).length) {
                const line = doc.createElement('p'); line.textContent = message; state.summary.append(line);
            }
        }
        state.first = first || (messages.size ? state.summary : null);
        return state.first;
    };
    const refresh = form => {
        const state = states.get(form);
        state.client = new Map([...collectIssues(form)].filter(([name]) => state.attempted || state.touched.has(name)));
        return paint(form);
    };
    const submit = event => {
        const form = event.target, state = states.get(form);
        if (!state || event.submitter?.formNoValidate) return;
        try {
            state.attempted = true; state.server.clear();
            const first = refresh(form);
            if (state.client.size) {event.preventDefault(); event.stopImmediatePropagation(); focus(first);}
        } catch {
            // Si falla la mejora, se recupera la comprobación nativa y decide el servidor.
            form.noValidate = false;
            if (!form.checkValidity()) {event.preventDefault(); event.stopImmediatePropagation(); form.reportValidity();}
        }
    };
    const edit = event => {
        const field = event.target, form = field.form, state = states.get(form);
        if (!state || !field.name || field.type === 'hidden') return;
        if (event.type === 'focusout') state.touched.add(field.name);
        const related = ['password','password_confirmation'].includes(field.name) ? ['password','password_confirmation'] : ['open_hour','close_hour'].includes(field.name) ? ['open_hour','close_hour'] : ['min_level','max_level','is_open'].includes(field.name) ? ['min_level','max_level','is_open'] : [field.name];
        for (const key of [...state.server.keys()]) if (related.some(name => key === dotted(name) || dotted(name).startsWith(key + '.'))) state.server.delete(key);
        refresh(form);
    };
    const reset = event => {
        const state = states.get(event.target); if (!state) return;
        state.server.clear(); state.client.clear(); state.touched.clear(); state.attempted = false; paint(event.target);
    };
    // Registro previo a screens.js y checkout.js: un envío inválido no bloquea botones.
    doc.addEventListener('submit',submit,true);
    for (const name of ['input','change','focusout']) doc.addEventListener(name,edit);
    doc.addEventListener('reset',reset);
    for (const form of forms) form.noValidate = true;
    let initial = null;
    try {
        const payload = JSON.parse(doc.getElementById('rp-validation-state')?.textContent || '{}');
        if (payload.errors && typeof payload.errors === 'object' && !Array.isArray(payload.errors)) {
            const errors = Object.fromEntries(Object.entries(payload.errors).filter(([, messages]) => Array.isArray(messages) && typeof messages[0] === 'string').map(([key, messages]) => [key,messages[0]]));
            const form = chooseServerForm(forms, errors, payload.form);
            if (form && Object.keys(errors).length) {
                const state = states.get(form); state.server = new Map(Object.entries(errors));
                initial = paint(form);
                const fallback = doc.querySelector('[data-validation-fallback]'); if (fallback) fallback.hidden = true;
            } else if (Object.keys(errors).length) initial = doc.querySelector('[data-validation-fallback]');
        }
    } catch { /* El aviso HTML del servidor sigue visible si no se puede leer el JSON. */ }
    if (initial) {
        if (doc.documentElement.classList.contains('rp-loading')) {
            const observer = new MutationObserver(() => {
                if (!doc.documentElement.classList.contains('rp-loading')) {observer.disconnect(); focus(initial);}
            });
            observer.observe(doc.documentElement,{attributes:true,attributeFilter:['class']});
        } else focus(initial);
    }
    const binding = {forms, states, refresh}; mounted.set(doc,binding); return binding;
}
if (typeof document !== 'undefined') enhanceValidation(document);
