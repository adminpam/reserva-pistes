{{-- Mensajes del servidor. data-sound le dice a app.js que tono reproducir. --}}
@if(session('ok'))
    <div class="flash flash-ok" data-sound="ok" role="status">{{ session('ok') }}</div>
@endif

@if($errors->any())
    <div class="flash flash-error" data-sound="error" role="alert" tabindex="-1" data-validation-fallback>
        <strong>Revisa los datos indicados</strong>
        @if($errors->count() === 1)
            {{ $errors->first() }}
        @else
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        @endif
    </div>
@endif
