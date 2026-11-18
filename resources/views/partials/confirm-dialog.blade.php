{{-- Diálogo nativo: conserva el foco y permite cancelar con Escape. --}}
<dialog id="rp-confirm-dialog" class="rp-confirm-dialog" aria-labelledby="rp-confirm-title" aria-describedby="rp-confirm-text">
    <h2 id="rp-confirm-title">Confirmar acción</h2>
    <p id="rp-confirm-text"></p>
    <div class="rp-confirm-dialog__actions">
        <button class="btn btn-ghost" type="button" data-confirm-cancel>Volver</button>
        <button class="btn btn-danger" type="button" data-confirm-accept>Confirmar</button>
    </div>
</dialog>
