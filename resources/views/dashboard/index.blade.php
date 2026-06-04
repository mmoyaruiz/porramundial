@extends('layouts.app')

@section('title', 'Panel de control')

@section('content')

<main class="dashboard-layout">

    {{-- SALUDO --}}
    <section class="dashboard-header">
        <h1>Hola, {{ $usuario->nombre_usuario }}</h1>
        <p>
            Este es tu panel principal. Desde aquí puedes ver un resumen de tus porras,
            unirte a nuevas porras y, si eres administrador, crear y gestionar las tuyas.
        </p>
    </section>

    {{-- TARJETAS RESUMEN --}}
    <section class="dashboard-cards">

        <div class="card">
            <h3>Porras en las que participas</h3>
            <p class="card-number">{{ $numParticipa }}</p>
            <p>Tienes {{ $numParticipa }} porras activas en este momento.</p>
        </div>

        <div class="card">
            <h3>Porras administradas</h3>
            <p class="card-number">{{ $numAdmin }}</p>
            <p>Estás administrando {{ $numAdmin }} porra(s) actualmente.</p>
        </div>

        <div class="card">
            <h3>Próximos partidos</h3>
            <p class="card-number">{{ $numPendientes }}</p>
            <p>Hay {{ $numPendientes }} partidos pendientes de pronóstico.</p>
        </div>

    </section>

    {{-- CONTENIDO PRINCIPAL --}}
    <section class="dashboard-main">

        {{-- MIS PORRAS --}}
        <div class="card">
            <h2>Mis porras</h2>

            <table class="table-responsive">
                <thead>
                    <tr>
                        <th>Porra</th>
                        <th>Competición</th>
                        <th>Estado</th>
                        <th>Tipo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($porras as $p)
                    <tr>
                        <td>{{ $p->nombre }}</td>
                        <td>{{ $p->competicion?->nombre }}</td>
                        <td>{{ $p->estado }}</td>
                        <td>{{ $p->es_publica ? 'Pública' : 'Privada' }}</td>
                        <td><a href="{{ route('porras.show', $p->id_porra) }}">Ver</a></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">Aún no participas en ninguna porra.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ACCIONES RÁPIDAS --}}
        <aside class="card">
            <h2>Acciones rápidas</h2>

            <!-- <a href="{{ route('porras.create') }}">Crear nueva porra</a> -->
             <a href="#">Crear nueva porra (en construcción)</a>

            <p class="help-text">
                <span><a href="{{ route('porras.disponibles') }}">Ver porras disponibles</a></span>
            </p>

            <p class="help-text">
                <span><a href="{{ route('porras.mis') }}">Ver porras en las que participas</a></span>

            </p>
            
             <p class="help-text">
                <span><a href="{{ route('porras.admin') }}">Ver porras que administras</a></span>
            </p>

        </aside>

    </section>

</main>

@endsection