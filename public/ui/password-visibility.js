// Mejora progresiva: sin JS, las contraseñas siguen siendo campos nativos ocultos.
const eye = '<svg viewBox="0 0 24 24" aria-hidden="true"><g class="rp-eye-open"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></g><g class="rp-eye-closed"><path d="m3 3 18 18M9.5 5.3A12 12 0 0 1 12 5c6.5 0 10 7 10 7a20 20 0 0 1-3 4.1M6 6.2A24 24 0 0 0 2 12s3.5 7 10 7a12 12 0 0 0 5.6-1.4M10 10a3 3 0 0 0 4 4"/></g></svg>';

export function setPasswordVisibility(input, button, visible) {
    const selection = [input.selectionStart, input.selectionEnd];
    input.type = visible ? 'text' : 'password';
    button.setAttribute('aria-pressed', String(visible));
    button.setAttribute('aria-label', `${visible ? 'Ocultar' : 'Mostrar'} ${button.dataset.passwordLabel || 'contraseña'}`);
    button.title = button.getAttribute('aria-label');
    try { if (selection[0] !== null && selection[1] !== null) input.setSelectionRange(...selection); } catch { /* Algunos teclados no permiten restaurarlo. */ }
}

export function enhancePasswords(root = document) {
    const bindings = [];
    for (const input of root.querySelectorAll('input[type="password"]')) {
        if (input.dataset.passwordEnhanced === 'true') continue;
        input.dataset.passwordEnhanced = 'true';
        const doc = input.ownerDocument;
        const wrapper = doc.createElement('span'); wrapper.className = 'rp-password-wrap';
        input.before(wrapper); wrapper.append(input);
        const button = doc.createElement('button'); button.className = 'rp-password-toggle'; button.type = 'button';
        button.innerHTML = eye; // SVG constante: nunca incluye contenido del usuario.
        button.dataset.passwordLabel = input.labels?.[0]?.textContent?.trim().toLocaleLowerCase('es') || 'contraseña';
        if (input.id) button.setAttribute('aria-controls', input.id);
        const hide = () => setPasswordVisibility(input, button, false);
        hide(); wrapper.append(button);
        button.addEventListener('click', () => {
            setPasswordVisibility(input, button, input.type === 'password');
            input.focus({preventScroll:true});
        });
        input.form?.addEventListener('submit', hide, {capture:true});
        input.form?.addEventListener('reset', hide);
        bindings.push({input, button, hide});
    }
    return bindings;
}
if (typeof document !== 'undefined') {
    const bindings = enhancePasswords(document);
    window.addEventListener('pagehide', () => bindings.forEach(binding => binding.hide()));
    window.addEventListener('pageshow', () => bindings.forEach(binding => binding.hide()));
}
