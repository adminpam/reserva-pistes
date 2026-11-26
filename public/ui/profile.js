// El navegador prepara una miniatura; el servidor vuelve a validar cada archivo.
export async function prepareAvatar(file, { loadImage = browserImage, makeCanvas = () => document.createElement('canvas'), encodeTimeoutMs = 15000 } = {}) {
    if (! ['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) throw new Error('Elige una imagen JPG, PNG o WebP.');
    if (file.size > 10 * 1024 * 1024) throw new Error('La imagen supera 10 MB. Elige una versión más pequeña.');
    const decoded = await loadImage(file);
    try {
        if (decoded.width < 32 || decoded.height < 32) throw new Error('La foto es demasiado pequeña. Usa una imagen de al menos 32 píxeles por lado.');
        const side = Math.min(decoded.width, decoded.height);
        const canvas = makeCanvas();
        canvas.width = canvas.height = 384;
        const context = canvas.getContext('2d');
        if (!context) throw new Error('No se ha podido preparar la foto. Prueba con otra imagen.');
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, 384, 384);
        context.drawImage(decoded.image, (decoded.width - side) / 2, (decoded.height - side) / 2, side, side, 0, 0, 384, 384);
        const blob = await new Promise((resolve, reject) => {
            const timer = setTimeout(() => reject(new Error('La foto tarda demasiado en prepararse. Elige otra imagen.')), encodeTimeoutMs);
            try {
                canvas.toBlob((result) => { clearTimeout(timer); resolve(result); }, 'image/jpeg', 0.85);
            } catch (error) { clearTimeout(timer); reject(error); }
        });
        if (!blob || blob.size > 512 * 1024 || !['image/jpeg', 'image/png'].includes(blob.type)) {
            throw new Error('No se ha podido reducir la foto. Prueba con una imagen más pequeña.');
        }
        return blob;
    } finally {
        decoded.cleanup();
    }
}

function browserImage(file) {
    return new Promise((resolve, reject) => {
        const source = URL.createObjectURL(file);
        const image = new Image();
        const cleanup = () => { image.onload = image.onerror = null; URL.revokeObjectURL(source); };
        const timeout = setTimeout(() => { cleanup(); image.src = ''; reject(new Error('La imagen tarda demasiado en abrirse. Prueba con otra foto.')); }, 15000);
        image.onload = () => {
            clearTimeout(timeout);
            resolve({ image, width: image.naturalWidth, height: image.naturalHeight, cleanup });
        };
        image.onerror = () => { clearTimeout(timeout); cleanup(); reject(new Error('No se puede abrir esta imagen. Elige otra foto.')); };
        image.src = source;
    });
}

export function bindAvatarForm(form, { root = document, prepare = prepareAvatar, urls = URL } = {}) {
    const input = form.querySelector('[data-avatar-input]');
    const submit = form.querySelector('[data-avatar-submit]');
    const status = form.querySelector('[data-avatar-status]');
    const current = root.querySelector('[data-avatar-current]');
    const preview = root.querySelector('[data-avatar-preview]');
    if (!input || !submit || !status || !current || !preview) return null;
    let generation = 0;
    let prepared = null;
    let previewURL = null;
    let busy = false;
    const resetPreview = () => {
        if (previewURL) urls.revokeObjectURL(previewURL);
        previewURL = null;
        preview.hidden = true;
        preview.removeAttribute('src');
        current.hidden = false;
    };
    const select = async () => {
        const ticket = ++generation;
        prepared = null;
        resetPreview();
        status.dataset.error = 'false';
        const file = input.files?.[0];
        if (!file) { busy = false; submit.disabled = false; status.textContent = ''; return; }
        busy = true;
        submit.disabled = true;
        status.textContent = 'Preparando tu foto…';
        try {
            const blob = await prepare(file);
            // Una preparación antigua nunca sustituye la última foto elegida.
            if (ticket !== generation) return;
            prepared = blob;
            previewURL = urls.createObjectURL(blob);
            preview.src = previewURL;
            preview.hidden = false;
            current.hidden = true;
            status.textContent = 'Vista previa lista. Pulsa Guardar foto para aplicarla.';
        } catch (error) {
            if (ticket !== generation) return;
            status.dataset.error = 'true';
            status.textContent = error instanceof Error ? error.message : 'No se ha podido preparar la foto.';
        } finally {
            if (ticket === generation) { busy = false; submit.disabled = !prepared; }
        }
    };
    const sendData = (event) => {
        if (prepared) event.formData.set('avatar', prepared, prepared.type === 'image/png' ? 'avatar.png' : 'avatar.jpg');
    };
    const submitForm = (event) => {
        if (busy || (input.files?.length && !prepared)) {
            event.preventDefault();
            status.textContent = busy ? 'Espera a que termine la preparación de la foto.' : 'Elige una foto válida antes de guardar.';
        }
    };
    input.addEventListener('change', select);
    form.addEventListener('formdata', sendData);
    form.addEventListener('submit', submitForm);
    const destroy = () => {
        generation++;
        prepared = null;
        resetPreview();
        input.removeEventListener('change', select);
        form.removeEventListener('formdata', sendData);
        form.removeEventListener('submit', submitForm);
    };
    return { select, destroy };
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('[data-avatar-form]').forEach((form) => {
        const binding = bindAvatarForm(form);
        if (binding) window.addEventListener('pagehide', (event) => { if (!event.persisted) binding.destroy(); }, { once: true });
    });
}
