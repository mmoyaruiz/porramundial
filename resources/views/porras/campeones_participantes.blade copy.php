@extends('layouts.app')

@section('title', 'Pronósticos de campeones')

@section('content')
<main class="home-layout">

    <section class="hero">
        <h1>Pronósticos de campeones</h1>
        <p><strong>Porra:</strong> {{ $porra->nombre }}</p>

        @if (!$competicionComenzada)
        <div class="alert alert-warning">
            Los pronósticos de campeones solo se pueden consultar cuando la competición ha comenzado.
        </div>
        @else
        <table class="table">
            <thead>
                <tr>
                    <th>Participante</th>
                    <th>Campeón <br>torneo</th>
                    @foreach($grupos as $grupo)
                    <th>Grupo <br>{{ $grupo }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($participantes as $p)
                @php
                $pc = $pronosticos[$p->id_usuario] ?? collect();

                $campeonTorneo = optional(
                $pc->firstWhere('tipo_pronostico', 'competicion')
                )->equipo_pronosticado;

                // índice por grupo
                $campeonesGrupo = $pc
                ->where('tipo_pronostico', 'grupo')
                ->keyBy('grupo');
                @endphp

                <tr>
                    <td>{{ $p->nombre_usuario }}</td>
                    <td>{{ $campeonTorneo ? ($mapNombreATla[$campeonTorneo] ?? $campeonTorneo) : '—' }}</td>
                    @foreach($grupos as $grupo)
                    <td>
                        @php
                        $eq = $campeonesGrupo[$grupo]->equipo_pronosticado ?? null;
                        @endphp
                        {{ $eq ? ($mapNombreATla[$eq] ?? $eq) : '—' }}
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </section>

    <aside class="sidebar">
        <h2>Navegación</h2>
        <p><a href="{{ route('porras.show', $porra->id_porra) }}">Volver a la porra</a></p>
        <p><a href="{{ route('porras.campeones', $porra->id_porra) }}">Ver mis campeones</a></p>
    </aside>

</main>
@endsection