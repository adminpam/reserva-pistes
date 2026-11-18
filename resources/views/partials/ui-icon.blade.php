{{-- Iconos comunes, decorativos: el texto contiguo aporta el significado. --}}
<svg class="rp-ui-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
@switch($name ?? '')
    @case('calendar') <rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4m10-4v4M3 10h18M7 14h1m4 0h1m4 0h1M7 17h1m4 0h1"/> @break
    @case('clock') <circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/> @break
    @case('pin') <path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/> @break
    @case('user') <circle cx="12" cy="7" r="4"/><path d="M4 21v-3a8 8 0 0 1 16 0v3"/> @break
    @case('check') <path d="m4 12 5 5L20 6"/> @break
    @case('info') <circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10v.1"/> @break
    @case('filter') <path d="M3 4h18l-7 8v7l-4 2v-9Z"/> @break
    @default <circle cx="12" cy="12" r="9"/><path d="M8 12h8M12 8v8"/>
@endswitch
</svg>
