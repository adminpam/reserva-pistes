{{-- Cabecera de pagina: foto de pista que se funde hacia el fondo claro.
     $image es opcional; sin ella el banner queda liso. --}}
@php($bannerImage = $image ?? asset('img/sports/tenis-cover.jpg'))
<div class="page-banner" style="--banner-img:url('{{ $bannerImage }}')">
    <div class="wrap">
        <h1>{{ $title }}</h1>
        @isset($subtitle)
            <p>{{ $subtitle }}</p>
        @endisset
        {{ $slot ?? '' }}
    </div>
</div>
