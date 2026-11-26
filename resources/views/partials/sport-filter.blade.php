{{-- Selección vacía = catálogo completo; Limpiar devuelve la ayuda inicial. --}}
<div class="rp-sport-filter" data-sport-filter="{{ $filterMode }}">
    <div class="rp-sport-filter__toolbar">
        <div class="rp-sport-guide" data-sport-guide @if($selectedSportSlugs !== [] || $sports->isEmpty()) hidden @endif>
            <svg class="rp-sport-guide__hand" viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <circle class="rp-sport-guide__touch" cx="15" cy="9" r="7"/>
                <path d="M12 24V10a3 3 0 0 1 6 0v10-3a3 3 0 0 1 6 0v4-2a3 3 0 0 1 6 0v4-1a3 3 0 0 1 6 0v7c0 5-3 8-8 8h-8c-3 0-5-1-7-4l-6-7a3 3 0 0 1 4-4l5 5"/>
            </svg>
            <div><strong>Selecciona algún deporte</strong><span>{{ $filterMode === 'map' ? 'Encuentra clubes con todos los que elijas.' : 'Elige uno o combina varias modalidades.' }}</span></div>
        </div>
        <span class="rp-sport-filter__caption" data-sport-caption @if($selectedSportSlugs === []) hidden @endif>{{ count($selectedSportSlugs) }} deportes seleccionados</span>
        <button type="button" class="rp-sport-clear" data-sport-clear aria-label="Limpiar los deportes seleccionados" hidden>Limpiar <span aria-hidden="true">×</span></button>
    </div>
    <div class="rp-sport-options rp-multi-sports" role="group" aria-label="Deportes: elige uno o varios">
        @foreach($sports as $sport)
            <div class="rp-sport-choice">
                <input type="checkbox" name="sports[]" id="{{ $filterPrefix }}-{{ $sport->slug }}" value="{{ $sport->slug }}" data-sport-option @checked(in_array($sport->slug, $selectedSportSlugs, true))>
                <label for="{{ $filterPrefix }}-{{ $sport->slug }}">
                    @include('partials.sport-icon', ['slug' => $sport->slug])
                    <span>{{ $sport->name }}</span>
                    <span class="rp-sport-choice__dot" aria-hidden="true"></span>
                </label>
            </div>
        @endforeach
    </div>
    <span class="rp-filter-summary" data-sport-summary role="status" aria-live="polite">{{ $selectedSportSlugs === [] ? 'Sin filtro de deporte' : count($selectedSportSlugs).' deportes seleccionados' }}</span>
</div>
