{{-- Un estado vacío se muestra solo si realmente no hay registros. --}}
<section class="rp-empty">
    <svg class="rp-empty-art" viewBox="0 0 240 160" fill="none" stroke="#075b49" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m15 130 40-42h134l37 42M30 115h181M120 88v42" stroke="#7cb5a0"/>
        <g transform="rotate(30 120 66)"><ellipse cx="120" cy="54" rx="23" ry="32" fill="#e4f4e9"/><path d="M105 33v43m10-51v60m10-61v61m10-52v43M99 43h43M97 54h46M99 65h42M113 85v38a7 7 0 0 0 14 0V85"/></g>
        <circle cx="185" cy="91" r="13" fill="#ffb21d"/><path d="M178 81c12 4 14 13 5 22"/>
        <path d="m188 28 6-6m7 22h8m-11 14 6 5" stroke="#ffb21d"/>
    </svg>
    <h2>{{ $title }}</h2>
    <p>{{ $text }}</p>
    @isset($href)<a class="btn btn-primary" href="{{ $href }}">{{ $label ?? 'Volver al mapa' }}</a>@endisset
</section>
