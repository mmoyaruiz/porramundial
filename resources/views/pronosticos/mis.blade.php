@extends('layouts.app')

@section('title', 'Mis pronósticos')

@section('content')
<main class="home-layout">

    {{-- CABECERA --}}
    <section class="hero">
        <h1>{{ $porra->nombre }}</h1>
        <h2>Mis pronósticos</h2>
        <p>
            Posición en la porra:
            <strong>{{ $participante->posicion }}</strong>
            <br>Puntos:
            <strong>{{ $participante->puntos }}</strong>
        </p>

        {{-- TABLA DE PRONÓSTICOS --}}
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Partido</th>
                    <th>Pronóstico</th>
                    <th>Estado</th>
                    <th>Marcador</th>
                </tr>
            </thead>

            <tbody>
                @foreach($partidos as $partido)
                @php
                $p = $pronosticos[$partido->id_partido] ?? null;
                @endphp
                <tr>
                    <td>
                        {{ \Carbon\Carbon::parse($partido->fecha_hora)->format('d/m/Y H:i') }}
                    </td>

                    <td>
                        {{ $partido->equipo_local_tla }}
                        vs
                        {{ $partido->equipo_visitante_tla }}
                    </td>

                    <td>
                        @if($p)
                        {{ $p->goles_local_pronosticados }} -
                        {{ $p->goles_visitante_pronosticados }}
                        @else
                        <em>No enviado</em>
                        @endif
                    </td>

                    <td>
                        {{ ucfirst($partido->estado) }}
                    </td>
                    <td>
                        @if($partido->goles_local !== null && $partido->goles_visitante !== null)
                        {{ $partido->goles_local }} -
                        {{ $partido->goles_visitante }}
                        @else
                        —
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    {{-- ASIDE --}}
    <aside class=sidebar>
        <h2>Acciones</h2>
        <p><a href="{{ route('porras.show', $porra->id_porra) }}">Volver a la porra</a></p>
        <p><a href="{{ route('tabla.partidos', $porra->id_porra) }}">Ver últimos partidos</a></p>
    </aside>

</main>
@endsection