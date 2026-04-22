@extends('layouts.app')

@section('title', 'Pronósticos de campeones')

@section('content')
<main class="home-layout">

    <section class="hero">
        <h1>Pronósticos de campeones</h1>

        {{-- Información de contexto --}}
        <p>
            <strong>Porra:</strong> {{ $porra->nombre }}
        </p>

        {{-- 
            Si la competición aún no ha comenzado, 
            no se muestran los pronósticos de otros participantes.
        --}}
        @if (!$competicionComenzada)

            <div class="alert alert-warning">
                Los pronósticos de campeones solo se pueden consultar cuando la competición ha comenzado.
            </div>

        @else

            {{-- Tabla de pronósticos de campeones por participante --}}
            <table class="table">
                <thead>
                    <tr>
                        <th>Participante</th>
                        <th>Campeón <br> torneo</th>

                        {{-- Una columna por cada grupo (A, B, C, ...) --}}
                        @foreach($grupos as $grupo)
                            <th>Grupo <br>{{ $grupo }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach($participantes as $p)

                        @php
                            /*
                             * Pronósticos del participante actual.
                             * Se agrupan previamente en el controlador por id_usuario.
                             */
                            $pc = $pronosticos[$p->id_usuario] ?? collect();

                            // Pronóstico de campeón del torneo
                            $campeonTorneo = optional(
                                $pc->firstWhere('tipo_pronostico', 'competicion')
                            )->equipo_pronosticado;

                            // Pronósticos de campeones de grupo indexados por letra de grupo
                            $campeonesGrupo = $pc
                                ->where('tipo_pronostico', 'grupo')
                                ->keyBy('grupo');
                        @endphp

                        <tr>
                            {{-- Nombre del participante --}}
                            <td>{{ $p->nombre_usuario }}</td>

                            {{-- Campeón del torneo --}}
                            <td>
                                {{ $campeonTorneo
                                    ? ($mapNombreATla[$campeonTorneo] ?? $campeonTorneo)
                                    : '—'
                                }}
                            </td>

                            {{-- Campeones de cada grupo --}}
                            @foreach($grupos as $grupo)
                                <td>
                                    @php
                                        $eq = $campeonesGrupo[$grupo]->equipo_pronosticado ?? null;
                                    @endphp

                                    {{ $eq
                                        ? ($mapNombreATla[$eq] ?? $eq)
                                        : '—'
                                    }}
                                </td>
                            @endforeach
                        </tr>

                    @endforeach
                </tbody>
            </table>

        @endif
    </section>

    {{-- Barra lateral de navegación --}}
    <aside class="sidebar">
        <h2>Navegación</h2>

        <p>
            <a href="{{ route('porras.show', $porra->id_porra) }}">
                Volver a la porra
            </a>
        </p>

        <p>
            <a href="{{ route('porras.campeones', $porra->id_porra) }}">
                Ver mis campeones
            </a>
        </p>
    </aside>

</main>
@endsection