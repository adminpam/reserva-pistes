/* =============================================================================
   ReservaPistes — comportamiento del front
   Cuatro módulos, cada uno con una responsabilidad:
     1. sonido   — tonos cortos sintetizados para clic, tecleo, éxito y error
     2. mapa     — Leaflet con los clubes y el filtro por deporte
     3. tiempo   — temperatura actual del club (Open-Meteo, sin clave)
     4. detalles — las pequeñas reacciones que hacen que la página se sienta viva
   ============================================================================= */

document.addEventListener('DOMContentLoaded', () => {
    Sonido.init();
    initMapa();
    initTiempo();
    initDetalles();
});

/* -----------------------------------------------------------------------------
   1. Capa de sonido
   Los tonos se generan con WebAudio: no hace falta ningún archivo de audio y
   el repositorio no engorda. Están apagados por defecto y la preferencia se
   guarda en localStorage. El navegador no deja sonar nada hasta que el usuario
   interactúa con la página, así que el contexto se crea en el primer gesto.
----------------------------------------------------------------------------- */
const Sonido = {
    activo: false,
    ctx: null,

    init() {
        try { this.activo = localStorage.getItem('rp-sonido') === 'on'; } catch { /* ignorar */ }

        const boton = document.getElementById('sound-toggle');
        if (boton) {
            this.pintarBoton(boton);
            boton.addEventListener('click', () => {
                this.activo = !this.activo;
                try { localStorage.setItem('rp-sonido', this.activo ? 'on' : 'off'); } catch { /* ignorar */ }
                this.pintarBoton(boton);
                if (this.activo) this.tocar('ok');   // confirmación al encenderlos
            });
        }

        // Un mensaje del servidor suena según su tipo.
        const flash = document.querySelector('[data-sound]');
        if (flash) this.tocar(flash.dataset.sound);

        this.engancharEventos();
    },

    pintarBoton(boton) {
        boton.setAttribute('aria-pressed', String(this.activo));
        boton.setAttribute('aria-label', this.activo ? 'Desactivar sonidos' : 'Activar sonidos');
        boton.querySelector('.snd-on').hidden  = !this.activo;
        boton.querySelector('.snd-off').hidden = this.activo;
    },

    // Cada acción tiene su timbre, para que el oído distinga qué ha pasado.
    recetas: {
        clic:  { notas: [[1320, 0, .035]],                 vol: .07, tipo: 'triangle' },
        tecla: { notas: [[880, 0, .022]],                  vol: .035, tipo: 'square'   },
        hover: { notas: [[1760, 0, .025]],                 vol: .025, tipo: 'sine'     },
        ok:    { notas: [[523, 0, .09], [784, .085, .14]], vol: .10, tipo: 'sine'     },
        error: { notas: [[233, 0, .13], [175, .1, .18]],   vol: .11, tipo: 'sine'     },
    },

    tocar(nombre) {
        if (!this.activo) return;
        const receta = this.recetas[nombre];
        if (!receta) return;

        try {
            this.ctx ||= new (window.AudioContext || window.webkitAudioContext)();
            if (this.ctx.state === 'suspended') this.ctx.resume();

            const ahora = this.ctx.currentTime;
            receta.notas.forEach(([hz, retraso, duracion]) => {
                const osc  = this.ctx.createOscillator();
                const gain = this.ctx.createGain();
                osc.type = receta.tipo;
                osc.frequency.value = hz;
                // Ataque muy corto y caída exponencial: suena a pulsación, no a pitido.
                gain.gain.setValueAtTime(0.0001, ahora + retraso);
                gain.gain.exponentialRampToValueAtTime(receta.vol, ahora + retraso + .008);
                gain.gain.exponentialRampToValueAtTime(0.0001, ahora + retraso + duracion);
                osc.connect(gain).connect(this.ctx.destination);
                osc.start(ahora + retraso);
                osc.stop(ahora + retraso + duracion + .02);
            });
        } catch { /* sin audio disponible: los sonidos son opcionales */ }
    },

    engancharEventos() {
        // Un solo listener delegado en lugar de uno por elemento.
        document.addEventListener('pointerdown', (e) => {
            if (e.target.closest('button, .btn, a, label, summary, .chip, [role="button"]')) {
                this.tocar('clic');
            }
        });

        document.addEventListener('pointerover', (e) => {
            if (e.target.closest('.row-card, .btn, .main-nav a, .slots label')) {
                this.tocar('hover');
            }
        });

        // Tecleo: las teclas de edición suenan distinto del texto normal.
        document.addEventListener('keydown', (e) => {
            if (!e.target.matches('input, textarea')) return;
            if (e.key === 'Enter')      this.tocar('clic');
            else if (e.key.length === 1 || e.key === 'Backspace') this.tocar('tecla');
        });
    },
};

/* -----------------------------------------------------------------------------
   2. Mapa de clubes
----------------------------------------------------------------------------- */
function initMapa() {
    const elMapa = document.getElementById('map');
    if (!elMapa || typeof L === 'undefined') return;

    const mapa = L.map(elMapa, { scrollWheelZoom: false }).setView([41.49, 2.13], 11);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(mapa);

    const capa    = L.layerGroup().addTo(mapa);
    const contador = document.getElementById('club-count');
    const filtros  = document.querySelectorAll('[name="sport-filter"]');

    async function cargar(slug = '') {
        const url = new URL(elMapa.dataset.clubsUrl, window.location.origin);
        if (slug) url.searchParams.set('sport', slug);

        try {
            const res = await fetch(url);
            const clubes = await res.json();

            capa.clearLayers();
            clubes.forEach((club) => {
                const marcador = L.marker([club.lat, club.lng], {
                    icon: L.divIcon({ className: '', html: '<div class="rp-marker"></div>', iconSize: [20, 20] }),
                });

                marcador.bindPopup(
                    `<strong>${escapar(club.name)}</strong><br>` +
                    `<span class="muted">${escapar(club.address || '')}</span><br>` +
                    `<em>${club.sports.map(escapar).join(' · ')}</em><br>` +
                    `<a href="${club.url}">Ver pistas y reservar →</a>`
                );

                // El marcador abierto respira, para señalar dónde se está mirando.
                marcador.on('popupopen', (e) => e.target.getElement()
                    ?.querySelector('.rp-marker')?.classList.add('is-active'));
                marcador.on('popupclose', (e) => e.target.getElement()
                    ?.querySelector('.rp-marker')?.classList.remove('is-active'));

                capa.addLayer(marcador);
            });

            if (contador) {
                contador.textContent = clubes.length === 1 ? '1 club' : `${clubes.length} clubes`;
            }
        } catch {
            if (contador) contador.textContent = 'No se han podido cargar los clubes.';
        }
    }

    filtros.forEach((f) => f.addEventListener('change', () => cargar(f.value)));
    cargar();
}

// Evita inyectar HTML en los globos del mapa.
function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

/* -----------------------------------------------------------------------------
   3. Tiempo actual (Open-Meteo, sin clave de API)
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
   4. Detalles vivos
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
