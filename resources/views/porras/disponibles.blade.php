@extends('layouts.app')
@section('title','Porras disponibles')

@section('content')
<main class="home-layout">
    <section class="hero">
        <h1>Porras disponibles</h1>
        <p>Porras públicas y porras privadas a las que has sido invitado.</p>

        <h2>Públicas</h2>
        @forelse($publicas as $p)
            <div class="porra-item">
                <strong>{{ $p->nombre }}</strong>
                <span>Competición: {{ $p->competicion?->nombre }}</span>
                <span><a href="{{ route('porras.show',$p->id_porra) }}">Ver</a></span>
            </div>
        @empty
            <p>No hay porras públicas disponibles.</p>
        @endforelse

        <h2 style="margin-top:18px;">Privadas (invitaciones)</h2>
        @forelse($privadasInvitadas as $p)
            <div class="porra-item">
                <strong>{{ $p->nombre }}</strong>
                <span>Competición: {{ $p->competicion?->nombre }}</span>
                <span><a href="{{ route('porras.show',$p->id_porra) }}">Ver</a></span>
            </div>
        @empty
            <p>No tienes invitaciones pendientes.</p>
        @endforelse
    </section>

    <aside class="sidebar">
        <h2>Accesos</h2>
        <p><a href="{{ route('porras.mis') }}">Mis porras</a></p>
        <p><a href="{{ route('porras.admin') }}">Porras que administro</a></p>
    </aside>
</main>
@endsection