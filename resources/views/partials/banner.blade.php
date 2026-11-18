{{-- Cabecera compartida de las pantallas internas. La imagen es ilustrativa. --}}
<section class="rp-page-banner" style="--rp-banner-image:url('{{ $image ?? asset('img/sports/tenis-cover.jpg') }}')">
    <div class="rp-page-banner__inner">
        <h1>{{ $title }}</h1>
        @isset($subtitle)<p>{{ $subtitle }}</p>@endisset
    </div>
</section>
