// JS del proyecto. Cuatro responsabilidades, cada una en su funcion:
// 1) initMap     — mapa Leaflet con los clubes y filtro por deporte
// 2) initWeather — tiempo actual del club (Open-Meteo, sin clave API)
// 3) initSounds  — dos sonidos cortos (exito/error) generados con WebAudio,
//                  desactivados por defecto; la preferencia se guarda en localStorage
// 4) initOpenMatchToggles — muestra los selectores de nivel al marcar "Partido abierto"

document.addEventListener('DOMContentLoaded', () => {
    initMap();
    initWeather();
    initSounds();
    initOpenMatchToggles();
});

// ---------- 1. Mapa de clubes ----------
function initMap() {
    const mapEl = document.getElementById('map');
    if (!mapEl || typeof L === 'undefined') return;

    // Centro inicial entre Sabadell y Barcelona.
    const map = L.map(mapEl).setView([41.49, 2.13], 11);

    // Teselas de OpenStreetMap con su atribucion obligatoria.
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    const layer = L.layerGroup().addTo(map);
    const counter = document.getElementById('club-count');
    const filter = document.getElementById('sport-filter');

    // Pide los clubes al backend y pinta un marcador por club.
    async function loadClubs() {
        const url = new URL(mapEl.dataset.clubsUrl, window.location.origin);
        if (filter && filter.value) url.searchParams.set('sport', filter.value);

        try {
            const response = await fetch(url);
            const clubs = await response.json();

            layer.clearLayers();
            clubs.forEach((club) => {
                const marker = L.marker([club.lat, club.lng], {
                    icon: L.divIcon({ className: '', html: '<div class="rp-marker"></div>', iconSize: [18, 18] }),
                });
                marker.bindPopup(
                    `<strong>${escapeHtml(club.name)}</strong><br>` +
                    `${escapeHtml(club.address || '')}<br>` +
                    `<em>${club.sports.map(escapeHtml).join(', ')}</em><br>` +
                    `<a href="${club.url}">Ver pistas y reservar</a>`
                );
                layer.addLayer(marker);
            });

            if (counter) counter.textContent = `${clubs.length} clubes`;
        } catch {
            if (counter) counter.textContent = 'No se han podido cargar los clubes.';
        }
    }

    if (filter) filter.addEventListener('change', loadClubs);
    loadClubs();
}

// Evita inyectar HTML en los popups del mapa.
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
}

// ---------- 2. Tiempo (Open-Meteo, sin clave) ----------
function initWeather() {
    const box = document.getElementById('weather');
    if (!box) return;

    const { lat, lng } = box.dataset;
    const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lng}&current=temperature_2m,weather_code&timezone=auto`;

    fetch(url)
        .then((r) => r.json())
        .then((data) => {
            const temp = Math.round(data.current.temperature_2m);
            box.innerHTML =
                `<div class="w-temp">${weatherEmoji(data.current.weather_code)} ${temp}°C</div>` +
                `<div class="w-desc">ahora en las pistas exteriores</div>`;
        })
        .catch(() => { box.hidden = true; });
}

// Traduce el weather_code de la OMM (que usa Open-Meteo) a un emoji simple.
function weatherEmoji(code) {
    if (code === 0) return '☀️';
    if (code <= 3) return '⛅';
    if (code <= 48) return '🌫️';
    if (code <= 67) return '🌧️';
    if (code <= 77) return '🌨️';
    if (code <= 82) return '🌧️';
    return '⛈️';
}

// ---------- 3. Sonidos de interfaz ----------
function initSounds() {
    const toggle = document.getElementById('sound-toggle');
    if (!toggle) return;

    let enabled = localStorage.getItem('rp-sounds') === 'on';
    render();

    toggle.addEventListener('click', () => {
        enabled = !enabled;
        localStorage.setItem('rp-sounds', enabled ? 'on' : 'off');
        render();
        if (enabled) beep('ok'); // pequeña confirmacion al activarlos
    });

    function render() {
        toggle.textContent = enabled ? '🔊' : '🔇';
        toggle.setAttribute('aria-pressed', String(enabled));
    }

    // Si hay un mensaje flash en la pagina, suena segun su tipo.
    const flash = document.querySelector('[data-sound]');
    if (flash && enabled) beep(flash.dataset.sound);
}

// Dos tonos cortos generados con WebAudio: no hacen falta archivos de audio.
// "ok" = dos notas ascendentes (turquesa sonoro); "error" = una nota grave.
function beep(kind) {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const notes = kind === 'error' ? [[220, 0, 0.18]] : [[523, 0, 0.09], [784, 0.1, 0.12]];

        notes.forEach(([freq, delay, duration]) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            gain.gain.setValueAtTime(0.12, ctx.currentTime + delay);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + duration);
            osc.connect(gain).connect(ctx.destination);
            osc.start(ctx.currentTime + delay);
            osc.stop(ctx.currentTime + delay + duration);
        });
    } catch {
        // Sin audio disponible: no pasa nada, los sonidos son opcionales.
    }
}

// ---------- 4. Opciones de partido abierto ----------
function initOpenMatchToggles() {
    document.querySelectorAll('.open-toggle').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            const options = checkbox.closest('form').querySelector('.open-options');
            if (options) options.hidden = !checkbox.checked;
        });
    });
}
