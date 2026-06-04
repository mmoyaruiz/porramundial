@extends('layouts.app')

@section('title', 'Detalle del partido')

@section('content')
<main class="home-layout">

    <section class="hero partido-header">

        <h3 class="partido-meta">
            <strong>Porra:</strong> {{ $porra->nombre }}

            <br><strong>Fase:</strong> {{ $partido->fase }}
            @if($partido->grupo)
            <br><strong>Grupo:</strong> {{ $partido->grupo }}
            @endif
            <br><strong>Fecha:</strong>
            {{ \Carbon\Carbon::parse($partido->fecha_hora)->format('d/m/Y H:i') }}
        </h3>

        <h1 class="partido-equipos">
            {{ $partido->equipo_local_shortname }}
            <span class="vs">vs</span>
            {{ $partido->equipo_visitante_shortname }}
        </h1>

        <h1 class="partido-marcador">
            {{ $partido->goles_local ?? '-' }}
            <span class="guion">-</span>
            {{ $partido->goles_visitante ?? '-' }}
        </h1>

        <h2 class="partido-marcador">
            @if($partido->estado === 'programado')
            <strong>Programado</strong>
            @elseif($partido->estado === 'en_juego')
            <strong>En juego</strong>
            @elseif($partido->estado === 'finalizado')
            <strong>Finalizado</strong>
            @endif
        </h2>
        <h3>Pronósticos de los participantes</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Pronóstico</th>
                    <th>1X2</th>
                </tr>
            </thead>
            <tbody>

                @foreach($pronosticos as $p)
                @php
                if ($p->goles_local_pronosticados > $p->goles_visitante_pronosticados) $signo = '1';
                elseif ($p->goles_local_pronosticados < $p->goles_visitante_pronosticados) $signo = '2';
                    else $signo = 'X';
                    $clase = '';
                    if ($haComenzado) {
                        if($partido->goles_local > 4) $partido->goles_local = 4;
                        if($partido->goles_visitante > 4) $partido->goles_visitante = 4;
                    if (
                    $p->goles_local_pronosticados === $partido->goles_local &&
                    $p->goles_visitante_pronosticados === $partido->goles_visitante
                    ) {
                    $clase = 'acierto-exacto';
                    } elseif (
                    ($partido->goles_local > $partido->goles_visitante && $signo === '1') ||
                    ($partido->goles_local < $partido->goles_visitante && $signo === '2') ||
                        ($partido->goles_local == $partido->goles_visitante && $signo === 'X')
                        ) {
                        $clase = 'acierto-signo';
                        }
                        }
                        @endphp

                        <tr class="{{ $clase }}">
                            <td>
                                {{ $p->nombre_usuario }}
                            </td>
                            <td>
                                {{ $p->goles_local_pronosticados }} - {{ $p->goles_visitante_pronosticados }}
                            </td>
                            <td>{{ $signo }}</td>
                        </tr>
                        @endforeach
            </tbody>
        </table>
        @if (!$haComenzado)
        <p>El pronóstico del resto de los participantes se mostrará una vez haya comenzado el partido</p>
        @endif

    </section>
    <aside class=sidebar>
        <h2>Acciones</h2>

        <p><a href="{{ route('porras.show', $porra->id_porra) }}">Volver a la porra</a></p>
        <p><a href="{{ route('tabla.partidos', $porra->id_porra) }}">Ver últimos partidos</a></p>

    </aside>

</main>
@endsection