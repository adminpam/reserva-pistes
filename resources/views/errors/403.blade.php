@extends('layouts.app')
@section('title', 'Sin permiso — ReservaPistes')
@section('content')
<div class="empty" style="padding-top:80px">
    <svg viewBox="0 0 200 140" style="width:200px" fill="none" stroke="#5E6F6B" stroke-width="2" stroke-linecap="round">
        <rect x="46" y="54" width="108" height="62" rx="6"/>
        <path d="M70 54V38a30 30 0 0 1 60 0v16" stroke="#E8A317"/>
        <circle cx="100" cy="82" r="8" fill="#E8A317" stroke="none"/>
        <path d="M100 90v12" stroke="#E8A317"/>
    </svg>
    <h1 style="font-size:2.5rem;margin-bottom:10px">No tienes permiso para entrar aquí</h1>
    <p>Esta zona es solo para gestores de club o administradores.</p>
    <a class="btn btn-ghost" href="{{ auth()->check() ? route('profile.edit') : route('login') }}">
        {{ auth()->check() ? 'Ir a mi perfil' : 'Entrar' }}
    </a>
</div>
@endsection
