@extends('layouts.app')
@section('title', 'Página no encontrada — ReservaPistes')
@section('content')
<div class="empty" style="padding-top:80px">
    <img src="{{ asset('img/empty.svg') }}" alt="" style="width:200px">
    <h1 style="font-size:2.5rem;margin-bottom:10px">Esta página se ha ido fuera</h1>
    <p>La dirección no existe o ha cambiado de sitio.</p>
    <a class="btn btn-primary" href="{{ route('home') }}">Volver al mapa</a>
</div>
@endsection
