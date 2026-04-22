@extends('layouts.app')

@section('title', 'Tabla de resultados')

@section('content')
<main class="home-layout">

    <section class="hero">
        <h1>Tabla de resultados</h1>

        {{-- Contexto de la porra --}}
        <p>
            Últimos partidos cerrados y en juego de la porra
            <strong>{{ $porra->nombre }}</strong>.
        </p>

        <h3>Pronósticos por participante (se muestran los últimos 10 partidos)</h3>

        {{-- ===================================================== --}}
        {{-- TABLA DE RESULTADOS --}}
        {{-- ===================================================== --}}
        <table class="table">

            <thead>
                {{-- Cabecera con partidos --}}
                <tr>
                    <th>Participante</th>

                    @foreach($partidos as $partido)
                        <th>
                            {{-- Enlace al detalle del partido --}}
                            <a
                                href="{{ route('porras.partido', [
                                    'idPorra'   => $porra->id_porra,
                                    'idPartido' => $partido->id_partido
                                ]) }}"
                                class="partido-link"
                            >
                                {{ $partido->equipo_local_tla }}<br>
                                vs<br>
                                {{ $partido->equipo_visitante_tla }}
                            </a>
                        </th>
                    @endforeach
                </tr>

                {{-- Fila de estado del partido --}}
                <tr>
                    <th>Estado</th>

                    @foreach($partidos as $partido)
                        <th>
                            @if($partido->estado === 'finalizado')
                                Fin
                            @else
                                En <br>juego
                            @endif
                        </th>
                    @endforeach
                </tr>

                {{-- Fila de marcador o fecha --}}
                <tr>
                    <th>Marcador</th>

                    @foreach($partidos as $partido)
                        <th>
                            @if($partido->estado === 'programado')
                                {{ \Carbon\Carbon::parse($partido->fecha_hora)->format('d/m/Y H:i') }}
                            @else
                                {{ $partido->goles_local }} - {{ $partido->goles_visitante }}
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                {{-- ===================================================== --}}
                {{-- PRONÓSTICOS POR PARTICIPANTE --}}
                {{-- ===================================================== --}}
                @foreach($participantes as $participante)

                    <tr>
                        {{-- Nombre del participante --}}
                        <td>
                            <strong>{{ $participante->nombre_usuario }}</strong>
                        </td>

                        @foreach($partidos as $partido)
                            @php
                                /*
                                 * Pronóstico del participante para este partido.
                                 * Los pronósticos vienen indexados como:
                                 * $pronosticos[id_usuario][id_partido]
                                 */
                                $p = $pronosticos[$participante->id_usuario][$partido->id_partido] ?? null;

                                /*
                                 * Se considera acierto exacto cuando:
                                 * - El partido tiene marcador real
                                 * - El pronóstico coincide exactamente
                                 */
                                $acierto = false;

                                if ($p && $partido->goles_local !== null) {
                                    $acierto =
                                        $p->goles_local_pronosticados == $partido->goles_local &&
                                        $p->goles_visitante_pronosticados == $partido->goles_visitante;
                                }
                            @endphp

                            {{-- Celda de pronóstico --}}
                            <td class="{{ $acierto ? 'acierto-exacto' : '' }}">
                                @if($p)
                                    {{ $p->goles_local_pronosticados }}-{{ $p->goles_visitante_pronosticados }}
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach
                    </tr>

                @endforeach
            </tbody>
        </table>
    </section>

    {{-- ===================================================== --}}
    {{-- BARRA LATERAL --}}
    {{-- ===================================================== --}}
    <aside class="sidebar">
        <h2>Acciones</h2>

        <p>
            <a href="{{ route('porras.show', $porra->id_porra) }}">
                Volver a la porra
            </a>
        </p>
    </aside>

</main>
@endsection