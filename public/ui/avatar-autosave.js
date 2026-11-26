/* Foto circular: preparación local, envíos en serie y versión comprobada por PHP.
   La cabecera solo muestra fotos confirmadas. La vista previa no significa guardado. */
import { prepareAvatar } from './profile.js?v=9665ebd904ee6b3c';

export function normalizeAvatar(payload, { origin, avatarEndpoint }) {
    const avatar = payload?.avatar;
    const invalid = () => new Error('La respuesta de la foto no es válida. Actualiza el perfil antes de repetir.');
    if (!avatar || typeof avatar.initials !== 'string' || !avatar.initials.trim() || avatar.initials.length > 20) throw invalid();
    if (avatar.url === null && avatar.version === null) return { url: null, version: null, initials: avatar.initials };
    if (typeof avatar.version !== 'string' || !/^[a-f0-9]{64}$/.test(avatar.version) || typeof avatar.url !== 'string') throw invalid();
    const base = new URL(origin);
    let url, expected;
    try { url = new URL(avatar.url, base); expected = new URL(avatarEndpoint, base); } catch { throw invalid(); }
    if (!['http:', 'https:'].includes(url.protocol) || url.origin !== base.origin || url.username || url.password || url.hash
        || url.pathname !== expected.pathname || url.searchParams.getAll('v').length !== 1 || url.searchParams.get('v') !== avatar.version) throw invalid();
    return { url: url.href, version: avatar.version, initials: avatar.initials };
}

function responseError(status, payload) {
    if (status === 401 || status === 419) return 'Tu sesión ha caducado. Actualiza la página para volver a entrar.';
    if (status === 413) return 'La foto enviada es demasiado grande. Prueba con una imagen más pequeña.';
    if (status === 422) {
        const errors = payload?.errors;
        const message = errors?.avatar?.[0] || errors?.expected_avatar_version?.[0];
        if (typeof message === 'string') return message.slice(0, 400);
        return 'No se ha podido validar la foto. Elige una imagen JPG, PNG o WebP.';
    }
    return 'No se ha podido confirmar el guardado. Elige de nuevo la foto para intentarlo.';
}

function readableError(error, fallback) {
    // Un fallo de una API nativa no se muestra como un mensaje técnico en el perfil.
    const native = ['TypeError', 'SecurityError', 'InvalidStateError', 'NotSupportedError', 'AbortError'];
    return error instanceof Error && !native.includes(error.name) ? error.message.slice(0, 400) : fallback;
}

export function createAvatarAutosave({ initial, send, decode, prepare = prepareAvatar, onState = () => {}, onConfirmed = () => {},
    timers = { set: (...args) => globalThis.setTimeout(...args), clear: (...args) => globalThis.clearTimeout(...args) }, prepareTimeoutMs = 35000, requestTimeoutMs = 20000 }) {
    let confirmed = { ...initial }, generation = 0, desired = null, pumping = false, disposed = false;
    let state = { phase: 'idle', message: '', preview: null };
    const cancellations = new Set();
    const emit = (phase, message, preview = state.preview) => {
        if (disposed) return;
        state = { phase, message, preview };
        onState({ ...state });
    };
    async function limited(operation, timeoutMs, message, controller = null) {
        let timer, rejectCancellation;
        const deadline = new Promise((resolve, reject) => {
            rejectCancellation = reject;
            timer = timers.set(() => { reject(new Error(message)); controller?.abort(); }, timeoutMs);
        });
        const cancel = () => { rejectCancellation(new Error('Operación cancelada.')); controller?.abort(); };
        cancellations.add(cancel);
        try { return await Promise.race([Promise.resolve().then(operation), deadline]); }
        finally { timers.clear(timer); cancellations.delete(cancel); }
    }
    const accept = (payload) => {
        confirmed = decode(payload);
        onConfirmed({ ...confirmed });
    };
    async function pump() {
        if (pumping || disposed) return;
        pumping = true;
        try {
            while (desired && !disposed) {
                const job = desired;
                desired = null;
                let retries = 0;
                while (job.ticket === generation && !disposed) {
                    const controller = new AbortController();
                    const expectedVersion = confirmed.version;
                    try {
                        const result = await limited(() => send(job.blob, expectedVersion, controller.signal), requestTimeoutMs,
                            'El servidor tarda demasiado. No se ha podido confirmar el guardado; vuelve a elegir la foto.', controller);
                        if (disposed) break;
                        if (result.status === 409) {
                            accept(result.payload);
                            // Solo se reintenta la selección vigente, una vez y con la nueva versión.
                            if (job.ticket === generation && retries++ === 0) continue;
                            if (job.ticket === generation) emit('error', 'La foto ha vuelto a cambiar. Elige de nuevo la imagen para intentarlo.', null);
                            break;
                        }
                        if (result.status < 200 || result.status >= 300) throw new Error(responseError(result.status, result.payload));
                        accept(result.payload);
                        if (job.ticket === generation) emit('saved', 'Foto guardada.', null);
                        break;
                    } catch (error) {
                        if (job.ticket === generation && !disposed) emit('error', readableError(error, responseError(0)), null);
                        break;
                    }
                }
            }
        } finally {
            pumping = false;
            if (desired && !disposed) void pump();
        }
    }
    async function select(file) {
        // Cancelar el selector nativo no cambia una subida que ya está en marcha.
        if (!file || disposed) return;
        const ticket = ++generation;
        desired = null;
        emit('preparing', 'Preparando foto…', null);
        try {
            const blob = await limited(() => prepare(file), prepareTimeoutMs, 'No se ha podido preparar la foto a tiempo. Elige otra imagen.');
            if (ticket !== generation || disposed) return;
            desired = { ticket, blob };
            emit('saving', 'Guardando foto…', blob);
            void pump();
        } catch (error) {
            if (ticket === generation && !disposed) emit('error', readableError(error, 'No se ha podido preparar la foto. Elige otra imagen e inténtalo de nuevo.'), null);
        }
    }
    return {
        select,
        snapshot: () => ({ ...state, confirmed: { ...confirmed } }),
        destroy() {
            disposed = true; generation++; desired = null; state = { ...state, preview: null };
            for (const cancel of cancellations) cancel();
        },
    };
}

export function createAvatarRequest({ action, csrf, origin, fetchImpl = fetch, makeData = () => new FormData() }) {
    const endpoint = new URL(action, origin);
    if (endpoint.origin !== new URL(origin).origin || !['http:', 'https:'].includes(endpoint.protocol) || endpoint.username || endpoint.password || !csrf) {
        throw new Error('No se puede iniciar el guardado. Actualiza el perfil.');
    }
    return async (blob, version, signal) => {
        const body = makeData();
        body.set('_token', csrf);
        body.set('expected_avatar_version', version ?? '');
        body.set('avatar', blob, blob.type === 'image/png' ? 'avatar.png' : 'avatar.jpg');
        let response;
        try {
            response = await fetchImpl(endpoint.href, { method: 'POST', body, signal, credentials: 'same-origin',
                cache: 'no-store', redirect: 'error', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
        } catch { throw new Error(responseError(0)); }
        let payload = null;
        try { payload = await response.json(); } catch { /* Un proxy puede devolver HTML de error. */ }
        return { status: response.status, payload };
    };
}

export function applyConfirmedAvatar(root, userId, avatar) {
    for (const element of root.querySelectorAll('[data-user-avatar]')) {
        if (element.dataset.userAvatar !== String(userId)) continue;
        element.dataset.avatarUrl = avatar.url ?? '';
        element.dataset.avatarVersion = avatar.version ?? '';
        element.dataset.avatarInitials = avatar.initials;
        if (avatar.url) {
            const image = element.ownerDocument.createElement('img');
            image.className = 'rp-avatar-image'; image.src = avatar.url; image.alt = ''; image.decoding = 'async';
            image.addEventListener('error', () => {
                if (element.dataset.avatarVersion === avatar.version && element.firstElementChild === image) element.textContent = avatar.initials;
            }, { once: true });
            element.replaceChildren(image);
        } else element.textContent = avatar.initials;
    }
}

const boundForms = new WeakMap();
export function bindAvatarAutosave(form, { root = form.ownerDocument, prepare = prepareAvatar, urls = URL, fetchImpl = fetch } = {}) {
    if (boundForms.has(form)) return boundForms.get(form);
    const input = form.querySelector('[data-avatar-input]');
    const pick = form.querySelector('[data-avatar-pick]');
    const status = form.querySelector('[data-avatar-status]');
    const preview = form.querySelector('[data-avatar-preview]');
    const current = form.querySelector('[data-user-avatar]');
    const versionInput = form.querySelector('[name="expected_avatar_version"]');
    if (!input || !pick || !status || !preview || !current || !versionInput) return null;
    const origin = root.defaultView.location.origin;
    const decode = (payload) => normalizeAvatar(payload, { origin, avatarEndpoint: form.dataset.avatarEndpoint });
    let initial, send;
    try {
        initial = decode({ avatar: { url: current.dataset.avatarUrl || null, version: current.dataset.avatarVersion || null, initials: current.dataset.avatarInitials } });
        send = createAvatarRequest({ action: form.action, csrf: form.querySelector('[name="_token"]')?.value, origin, fetchImpl });
    } catch (error) {
        status.textContent = error.message; form.dataset.avatarState = 'error'; return null;
    }
    let previewBlob = null, previewURL = null;
    const resetPreview = () => {
        if (previewURL) urls.revokeObjectURL(previewURL);
        previewBlob = previewURL = null; preview.hidden = true; preview.removeAttribute('src'); current.hidden = false;
    };
    const machine = createAvatarAutosave({ initial, send, prepare, decode,
        onConfirmed(avatar) {
            versionInput.value = avatar.version ?? '';
            applyConfirmedAvatar(root, form.dataset.avatarUser, avatar);
        },
        onState(state) {
            form.dataset.avatarState = state.phase;
            form.setAttribute('aria-busy', String(['preparing', 'saving'].includes(state.phase)));
            status.textContent = state.message;
            if (state.preview !== previewBlob) {
                resetPreview();
                if (state.preview) {
                    try {
                        previewURL = urls.createObjectURL(state.preview); previewBlob = state.preview;
                        preview.src = previewURL; preview.hidden = false; current.hidden = true;
                    } catch { resetPreview(); /* La miniatura puede guardarse aunque falle su vista previa. */ }
                }
            }
        },
    });
    const choose = () => input.click();
    const changed = () => { const file = input.files?.[0]; input.value = ''; void machine.select(file); };
    const submit = (event) => event.preventDefault();
    pick.disabled = false;
    pick.addEventListener('click', choose);
    input.addEventListener('change', changed);
    form.addEventListener('submit', submit);
    const binding = { ...machine, destroy() {
        machine.destroy(); resetPreview(); pick.disabled = true;
        pick.removeEventListener('click', choose); input.removeEventListener('change', changed); form.removeEventListener('submit', submit);
        if (boundForms.get(form) === binding) boundForms.delete(form);
    } };
    boundForms.set(form, binding);
    return binding;
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-avatar-autosave]').forEach((form) => {
        const binding = bindAvatarAutosave(form);
        if (binding) window.addEventListener('pagehide', (event) => { if (!event.persisted) binding.destroy(); });
    });
}
