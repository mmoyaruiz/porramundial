@extends('layouts.app')

@section('title', 'PORRAMUNDIAL.COM - Inicio')

@section('content')
<div class="home-layout">

    <section class="hero">
        <h1>Bienvenido a PORRAMUNDIAL.COM</h1>

        <p>
            PORRAMUNDIAL.COM es una aplicación web para organizar porras de fútbol
            entre amigos, familiares o compañeros de trabajo.
        </p>

        <p>
            Crea porras públicas o privadas, configura tus reglas de puntuación
            e invita participantes para competir pronosticando resultados.
        </p>

        <div class="hero-actions">
            <a href="{{ route('register') }}" class="btn btn-primary">Crear cuenta</a>
            <a href="{{ route('login') }}" class="btn btn-secondary">Ya tengo cuenta</a>
        </div>
    </section>

    

</div>
@endsection