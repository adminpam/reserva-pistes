{{-- Los partidos activos, jugados y cancelados tienen estados distintos. --}}
@extends('layouts.app')
@section('title', 'Mis partidos — Reserva Pistes')
@section('banner')
    @include('partials.banner', ['title' => 'Mis partidos', 'subtitle' => 'Gestiona los partidos que organizas o en los que participas.'])
@endsection
@section('content')
@php
    $activos = $matches->filter(fn ($m) => $m->status !== 'cancelled' && $m->ends_at->isFuture())->sortBy('starts_at');
    $pasados = $matches->filter(fn ($m) => $m->status !== 'cancelled' && $m->ends_at->isPast());
    $cancelados = $matches->where('status', 'cancelled');
@endphp
@foreach(['Mis partidos activos' => $activos, 'Mis partidos pasados' => $pasados, 'Partidos cancelados' => $cancelados] as $heading => $items)
    @if($items->isNotEmpty())
        <h2 class="rp-section-heading">{{ $heading }}</h2>
        <div class="rp-list">@foreach($items as $match)@include('matches._row', ['match' => $match, 'pasado' => $heading !== 'Mis partidos activos'])@endforeach</div>
    @endif
@endforeach
@if($matches->isEmpty())
    @include('partials.empty-state', ['title' => 'Aún no tienes partidos', 'text' => 'Organiza un partido o únete a alguno de los que hay abiertos.', 'href' => route('matches.index'), 'label' => 'Buscar partidos'])
@endif
@endsection
