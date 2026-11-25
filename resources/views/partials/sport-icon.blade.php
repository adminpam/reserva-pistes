{{-- Conjunto aprobado por Denys. El nombre contiguo es la etiqueta accesible. --}}
@php($illustratedSports = ['tenis-de-mesa', 'badminton', 'baloncesto-3x3', 'voleibol', 'futbol-sala', 'tenis', 'padel'])
@if(in_array($slug ?? '', $illustratedSports, true))
<span class="rp-sport-icon rp-sport-art" aria-hidden="true">
    <img class="rp-sport-art__normal" src="{{ \App\Support\FrontendAsset::url('img/sport-icons/'.$slug.'.svg') }}" alt="" width="128" height="128" decoding="async">
    <img class="rp-sport-art__dark" src="{{ \App\Support\FrontendAsset::url('img/sport-icons/'.$slug.'-on-dark.svg') }}" alt="" width="128" height="128" decoding="async">
</span>
@else
{{-- Iconos vectoriales propios. El nombre accesible está en la etiqueta del filtro. --}}
<svg class="rp-sport-icon" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
    @switch($slug)
        @case('tenis-de-mesa')
            <ellipse cx="18" cy="12" rx="9" ry="10" transform="rotate(36 18 12)"/>
            <path d="m10 18-7 8a2.8 2.8 0 0 0 4 4l7-8M11 19l5 4M12 7c-2 3-1 7 2 9"/>
            @break
        @case('badminton')
            <path d="m7 22 9-19 4 1-1 13M7 22 25 8l3 3-10 13M7 22 24 4l3 2-8 14M7 22l10-4M6 22a5 5 0 0 0 7 7l4-5-7-6z"/>
            @break
        @case('baloncesto-3x3')
            <circle cx="16" cy="16" r="13"/>
            <path d="M3 16h26M16 3v26M7 6c11 6 11 14 18 20M25 6C14 12 14 20 7 26"/>
            @break
        @case('voleibol')
            <circle cx="16" cy="16" r="13"/>
            <path d="m16 16 1-13M16 16l11 7M16 16 5 23M10 4c1 7 6 8 13 8M29 12c-7 1-10 5-10 14M10 28c1-8-2-12-7-14"/>
            @break
        @case('futbol-sala')
            <circle cx="16" cy="16" r="13"/>
            <path d="m16 10 6 4-2 7h-8l-2-7zM16 3v7M28 12l-6 2M24 27l-4-6M8 27l4-6M4 12l6 2M7 6l-1 5M25 6l1 5M28 21l-3 5M4 21l3 5"/>
            @break
        @case('tenis')
            <circle cx="16" cy="16" r="13"/>
            <path d="M8 6c10 5 10 15 0 20M24 6c-10 5-10 15 0 20"/>
            @break
        @default
            <rect x="3" y="6" width="26" height="20" rx="3"/>
            <path d="M16 6v20"/><circle cx="16" cy="16" r="6"/>
    @endswitch
</svg>

@endif
