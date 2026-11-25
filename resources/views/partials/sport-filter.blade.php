{{-- Los checkboxes conservan su semántica nativa con teclado y lector de pantalla. --}}
<div class="rp-sport-options rp-multi-sports" role="group" aria-label="Deportes: elige uno o varios" data-sport-filter="{{ $filterMode }}">
    <div class="rp-sport-choice rp-sport-choice--all">
        <button type="button" class="rp-sport-all" data-sport-all aria-pressed="{{ $selectedSportSlugs === [] ? 'true' : 'false' }}" hidden>
            @include('partials.sport-icon', ['slug' => 'generic'])
            <span>Todos</span><span class="rp-sport-choice__dot" aria-hidden="true"></span>
        </button>
    </div>
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
    <span class="rp-filter-summary" data-sport-summary role="status" aria-live="polite">{{ $selectedSportSlugs === [] ? 'Todos los deportes' : count($selectedSportSlugs).' deportes seleccionados' }}</span>
</div>
