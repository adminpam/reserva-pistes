{{-- Cargador compartido con la entrada estática. No contiene una duración fija:
     loading.js lo retira cuando la página está preparada. La barra es indeterminada. --}}
<section class="rp-loader" data-rp-loader data-mode="page" aria-label="Cargando Reserva Pistes">
    <div class="rp-loader__scene">
        <svg class="rp-loader__court" viewBox="340 150 980 360" aria-hidden="true" focusable="false">
            <defs>
                <filter id="rp-ball-halo" x="-200%" y="-200%" width="500%" height="500%">
                    <feGaussianBlur stdDeviation="18"/>
                </filter>
                <radialGradient id="rp-ball-fill">
                    <stop offset="0" stop-color="#fff4cf"/>
                    <stop offset="1" stop-color="#ffe0a2"/>
                </radialGradient>
                <linearGradient id="rp-court-line" x1="652" y1="360" x2="1014" y2="244" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#d68c19"/>
                    <stop offset="1" stop-color="#ffb433"/>
                </linearGradient>
            </defs>
            <path class="rp-loader__floor" d="M397 337 944 203 1253 262 729 453Z"/>
            <g class="rp-loader__lines">
                <path d="M500 312 830 414M890 217 1183 285"/>
                <path d="M652 360 1014 244" style="stroke:url(#rp-court-line)"/>
            </g>
            <ellipse class="rp-loader__shadow" cx="842" cy="296" rx="35" ry="9" fill="#ffb433" opacity=".6" filter="url(#rp-ball-halo)"/>
            <g class="rp-loader__ball">
                <circle cx="842" cy="296" r="32" fill="#ffb326" opacity=".7" filter="url(#rp-ball-halo)"/>
                <circle cx="842" cy="296" r="14" fill="url(#rp-ball-fill)" stroke="#ffb433" stroke-width="6"/>
            </g>
        </svg>
        <div class="rp-loader__name" aria-hidden="true"><span>Reserva</span> <span>Pistes</span></div>
        <div class="rp-loader__track" role="progressbar" aria-label="Preparando Reserva Pistes"></div>
        <p class="rp-loader__notice" data-rp-notice hidden></p>
        <div class="rp-loader__actions" data-rp-actions hidden>
            <button class="rp-loader__button" type="button" data-rp-retry>Reintentar</button>
            <a class="rp-loader__button" data-rp-direct href="https://reserva-pistes.onrender.com/">Abrir la aplicación</a>
        </div>
        <span class="rp-loader__sr" role="status" aria-live="polite" data-rp-status>Preparando Reserva Pistes.</span>
    </div>
</section>
