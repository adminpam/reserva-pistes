// El mapa de la portada se coordina en public/ui/home.js.

/* =============================================================================
   ReservaPistes — comportamiento del front
   Sonido: public/ui/sounds.js, cargado con versión desde el layout.
   Aquí permanecen el tiempo y los detalles de los formularios.
   ============================================================================= */

document.addEventListener('DOMContentLoaded', () => {
    initTiempo();
    initDetalles();
});

/* -----------------------------------------------------------------------------
   2. Tiempo actual (Open-Meteo, sin clave de API)
----------------------------------------------------------------------------- */
function initTiempo() {
    const caja = document.getElementById('weather');
    if (!caja) return;

    const { lat, lng } = caja.dataset;
    const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lng}` +
                `&current=temperature_2m,weather_code&timezone=auto`;

    fetch(url)
        .then((r) => r.json())
        .then((d) => {
            const temp = Math.round(d.current.temperature_2m);
            caja.innerHTML =
                `<span class="weather-icon">${emojiTiempo(d.current.weather_code)}</span>` +
                `<span><span class="num">${temp}°C</span>` +
                `<span class="tiny">ahora en las pistas exteriores</span></span>`;
            caja.hidden = false;
        })
        .catch(() => { caja.hidden = true; });
}

// Códigos de la OMM que usa Open-Meteo, resumidos a un icono.
function emojiTiempo(codigo) {
    if (codigo === 0)   return '☀️';
    if (codigo <= 3)    return '⛅';
    if (codigo <= 48)   return '🌫️';
    if (codigo <= 67)   return '🌧️';
    if (codigo <= 77)   return '🌨️';
    if (codigo <= 82)   return '🌧️';
    return '⛈️';
}

/* -----------------------------------------------------------------------------
   3. Detalles vivos
----------------------------------------------------------------------------- */
function initDetalles() {
    // Las opciones de nivel solo aparecen si el partido es abierto.
    document.querySelectorAll('.open-toggle').forEach((casilla) => {
        const caja = casilla.closest('form')?.querySelector('.open-options');
        if (!caja) return;
        caja.hidden = !casilla.checked;
        casilla.addEventListener('change', () => { caja.hidden = !casilla.checked; });
    });

    // Al enviar, el botón pasa a "enviando" y se bloquea: evita dobles reservas.
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', () => {
            const boton = form.querySelector('button[type="submit"], .btn-primary');
            if (boton && !boton.dataset.quieto) {
                boton.classList.add('is-disabled');
                boton.dataset.textoOriginal = boton.textContent;
                boton.textContent = 'Un momento…';
            }
        });
    });

    // Los mensajes del servidor se retiran solos a los 6 s.
    document.querySelectorAll('.flash-ok').forEach((flash) => {
        setTimeout(() => {
            flash.style.transition = 'opacity .4s ease, transform .4s ease';
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-8px)';
            setTimeout(() => flash.remove(), 420);
        }, 6000);
    });
}
