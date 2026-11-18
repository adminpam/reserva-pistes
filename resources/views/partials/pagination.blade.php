{{-- Paginación propia, sin depender de las clases de Tailwind de Laravel. --}}
@if($paginator->hasPages())
<nav aria-label="Paginación">
    <ul class="rp-pagination">
        <li>@if($paginator->onFirstPage())<span aria-disabled="true">Anterior</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">Anterior</a>@endif</li>
        @foreach($elements as $element)
            @if(is_string($element))<li class="rp-pagination-page"><span>{{ $element }}</span></li>@endif
            @if(is_array($element))
                @foreach($element as $page => $url)
                    <li class="rp-pagination-page">@if($page === $paginator->currentPage())<span aria-current="page">{{ $page }}</span>@else<a href="{{ $url }}" aria-label="Página {{ $page }}">{{ $page }}</a>@endif</li>
                @endforeach
            @endif
        @endforeach
        <li>@if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente</a>@else<span aria-disabled="true">Siguiente</span>@endif</li>
    </ul>
    <p class="rp-pagination-summary">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</p>
</nav>
@endif
