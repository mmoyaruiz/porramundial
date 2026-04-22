@extends('layouts.app')
@section('title','Mis porras')

@section('content')
<main class="home-layout">
    <section class="hero">
        <h1>Porras en las que participo</h1>
        <p>Listado de porras en las que participas actualmente.</p>

        <table class="table">
            <thead>
                <tr>
                    <th>Porra</th>
                    <th>Estado</th>
                    <th>Competición</th>
                    <th>Tu posición</th>
                    <th>Participantes</th>
                    <th>Pronósticos pendientes</th>
                    <th>Horas hasta cierre</th>
                </tr>
            </thead>
            <tbody>
                @forelse($porras as $p)
                <tr>
                    <td><strong><a href="{{ route('porras.show', $p->id_porra) }}">{{ $p->nombre }}</a></strong></td>
                    <td>
                        <span class="badge badge-{{ $p->estado }}">
                            {{ ucfirst($p->estado) }}
                        </span>
                    </td>
                    <td>{{ $p->competicion?->nombre }}</td>
                    <td>{{ $p->mi_posicion ?? '-' }}</td>
                    <td>{{ $p->num_participantes }}</td>
                    <td>{{ $p->pronosticos_pendientes }} partidos</td>
                    <td>{{ $p->horas_hasta_cierre }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">Aún no participas en ninguna porra.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <aside class="sidebar">
        <h2>Acciones rápidas</h2>
        <p><a href="{{ route('porras.create') }}">Crear nueva porra</a></p>
        <p><a href="{{ route('porras.admin') }}">Porras que administro</a></p>
    </aside>
</main>
@endsection