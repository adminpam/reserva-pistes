{{-- Partidos que organizo o en los que participo, separados en activos y jugados. --}}
@extends('layouts.app')
@section('title', 'Mis partidos — ReservaPistes')

@section('banner')
    @include('partials.banner', [
        'title'    => 'Mis partidos',
        'subtitle' => 'Gestiona los partidos que organizas o en los que participas.',
        'image'    => \App\Support\Ui::sportCover('voleibol'),
    ])
@endsection

@section('content')
@php
    $activos = $matches->filter(fn ($m) => $m->status !== 'cancelled' && $m->starts_at->isFuture());
    $pasados = $matches->reject(fn ($m) => $m->status !== 'cancelled' && $m->starts_at->isFuture());
@endphp

@if($activos->isNotEmpty())
    <h2 class="section-title">Mis partidos activos</h2>
    <div class="stagger">
        @foreach($activos as $match)
            @include('matches._row', ['match' => $match, 'pasado' => false])
        @endforeach
    </div>
@endif

@if($pasados->isNotEmpty())
    <h2 class="section-title mt-xl">Mis partidos pasados</h2>
    <div>
        @foreach($pasados as $match)
            @include('matches._row', ['match' => $match, 'pasado' => true])
        @endforeach
    </div>
@endif

@if($matches->isEmpty())
    <div class="empty">
        <img src="{{ asset('img/empty.svg') }}" alt="">
        <h3>Aún no tienes partidos</h3>
        <p>Organiza un partido desde la ficha de un club o únete a alguno de los que hay abiertos.</p>
        <a class="btn btn-primary" href="{{ route('matches.index') }}">Buscar partidos</a>
    </div>
@endif
@endsection
