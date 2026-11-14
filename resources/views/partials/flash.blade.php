{{-- Mensajes flash: exito (verde azulado) y errores de validacion (rojo).
     data-sound indica a app.js que sonido reproducir si estan activados. --}}
@if(session('ok'))
    <div class="flash flash-ok" data-sound="ok" role="status">{{ session('ok') }}</div>
@endif

@if($errors->any())
    <div class="flash flash-error" data-sound="error" role="alert">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
