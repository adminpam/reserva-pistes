/* Interacciones de las pantallas internas: confirmaciones y selección de hora.
   Todas las operaciones definitivas siguen siendo formularios del servidor. */
const dialog = document.getElementById('rp-confirm-dialog');
let pending = null;
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;
    if (form.dataset.rpConfirmed === '1') { delete form.dataset.rpConfirmed; return; }
    event.preventDefault();
    event.stopImmediatePropagation();
    const submitter = event.submitter;
    const resume = () => {
        form.dataset.rpConfirmed = '1';
        form.requestSubmit(submitter || undefined);
    };
    if (!dialog || typeof dialog.showModal !== 'function') {
        if (window.confirm(form.dataset.confirm)) resume();
        return;
    }
    pending = resume;
    dialog.querySelector('#rp-confirm-text').textContent = form.dataset.confirm;
    dialog.showModal();
    dialog.querySelector('[data-confirm-cancel]').focus();
}, true);
dialog?.querySelector('[data-confirm-cancel]')?.addEventListener('click', () => { pending = null; dialog.close(); });
dialog?.querySelector('[data-confirm-accept]')?.addEventListener('click', () => {
    const resume = pending;
    pending = null; dialog.close(); resume?.();
});
dialog?.addEventListener('close', () => { pending = null; });

document.querySelectorAll('[data-booking-form]').forEach((form) => {
    const summary = form.querySelector('[data-slot-summary]');
    const update = () => {
        const selected = form.querySelector('[name="hour"]:checked');
        if (!summary) return;
        if (!selected) { summary.textContent = 'Elige una hora disponible.'; return; }
        const hour = Number(selected.value);
        summary.textContent = `${String(hour).padStart(2, '0')}:00–${String(hour + 1).padStart(2, '0')}:00 · 1 hora`;
    };
    form.addEventListener('change', update); update();
});
