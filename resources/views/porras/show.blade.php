@extends('layouts.app')

@section('title', $porra->nombre)

@section('content')
<main class="home-layout">

    <section class="hero">

        {{-- Mensaje de éxito --}}
        @if(session('success'))
        <div class="alert-success">{{ session('success') }}</div>
        @endif

        {{-- Mensaje de error general --}}
        @if($errors->has('general'))
        <div class="alert-error">{{ $errors->first('general') }}</div>
        @endif

        {{-- Información principal de la porra --}}
        <h1>{{ $porra->nombre }}</h1>
        <p>{{ $porra->descripcion }}</p>

        <p>
            <strong>Competición:</strong> {{ $porra->competicion?->nombre }}
        </p>
        <p>
            <strong>Estado:</strong> {{ $porra->estado }}
        </p>

        @php
        /*
        * Comprobamos si el usuario logueado ya participa en la porra.
        * Esto se usa para mostrar el botón "Unirse" o el mensaje informativo.
        */
        $user = session('usuario');

        $yaParticipa = $user
        ? \App\Models\Participacion::where('id_usuario', $user->id_usuario)
        ->where('id_porra', $porra->id_porra)
        ->exists()
        : false;
        @endphp

        {{-- Bloque unirse / ya participa --}}
        @if(!$yaParticipa)
        <form action="{{ route('porras.unirse', $porra->id_porra) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">
                Unirse a esta porra
            </button>
        </form>
        @else
        <div class="hero-highlight">
            ✅ Ya participas en esta porra.
        </div>
        @endif

        {{-- Se muestra un mensaje de respuesta tras actualizar partidos y clasificación desde la API.--}}

        @if(session()->has('status_msg'))
        @if(session('status_ok'))
        <div class="alert alert-success">
            <strong>{{ session('status_msg') }}</strong>
            @if(session('import_output'))
            <div class="alert-details">{{ session('import_output') }}</div>
            @endif
            @if(session('recalc_output'))
            <div class="alert-details">{{ session('recalc_output') }}</div>
            @endif

        </div>
        @else
        <div class="alert alert-error">
            <strong>{{ session('status_msg') }}</strong>
            @if(session('import_output'))
            <div class="alert-details">{{ session('import_output') }}</div>
            @endif
            @if(session('recalc_output'))
            <div class="alert-details">{{ session('recalc_output') }}</div>
            @endif

        </div>
        @endif
        @endif

        <hr>

        {{-- ===================================================== --}}
        {{-- CLASIFICACIÓN ACTUAL --}}
        {{-- ===================================================== --}}
        <h2>Clasificación actual</h2>

        <table class="table">
            <thead>
                <tr>
                    <th>Posición</th>
                    <th>Usuario</th>
                    <th>Puntos</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clasificacion as $i => $fila)
                <tr>
                    <td>{{ $i + 1 }}</td>

                    {{-- El usuario logueado se muestra sin enlace --}}
                    @if($fila->id_usuario === session('usuario')->id_usuario)
                    <td><strong>{{ $fila->nombre_usuario }}</strong></td>
                    @else
                    <td>
                        <a href="{{ route('porras.participante', [
                                    'idPorra' => $porra->id_porra,
                                    'idUsuario' => $fila->id_usuario
                                ]) }}">
                            {{ $fila->nombre_usuario }}
                        </a>
                    </td>
                    @endif

                    <td>{{ $fila->puntos }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- ===================================================== --}}
        {{-- PRÓXIMOS PARTIDOS Y ESTADO DE PRONÓSTICOS --}}
        {{-- ===================================================== --}}
        <h2>Próximos partidos y estado de pronósticos</h2>

        @php
        /*
        * Solo se muestran partidos:
        * - programados
        * - en juego
        * Los partidos finalizados no aparecen en esta sección.
        */
        $partidosVisibles = $proximosPartidos->filter(function ($partido) {
        return in_array($partido->estado, ['programado', 'en_juego']);
        });
        @endphp

        @if($partidosVisibles->isEmpty())

        <p>No hay partidos programados o en juego.</p>

        @else

        <table class="table">
            <thead>
                <tr>
                    <th>Fase</th>
                    <th>Fecha</th>
                    <th>Partido</th>
                    <th>Mi <br>pronóstico</th>
                    <th>1X2</th>
                    <th>Horas hasta <br>el cierre</th>
                    <th>Marcador</th>
                    <th>Detalle de <br>partido</th>
                </tr>
            </thead>

            <tbody>
                @foreach($partidosVisibles as $partido)

                @php
                /*
                * Pronóstico del usuario para este partido (si existe).
                */
                $pronostico = $misPronosticos[$partido->id_partido] ?? null;

                /*
                * Cálculo del signo 1X2 a partir del pronóstico.
                */
                if (!$pronostico) {
                $signo = 'Pendiente';
                } elseif ($pronostico->goles_local_pronosticados > $pronostico->goles_visitante_pronosticados) {
                $signo = '1';
                } elseif ($pronostico->goles_local_pronosticados < $pronostico->goles_visitante_pronosticados) {
                    $signo = '2';
                    } else {
                    $signo = 'X';
                    }

                    /*
                    * Texto de la columna "Horas hasta el cierre":
                    * - En juego → "En juego"
                    * - Programado → tiempo restante
                    */
                    if ($partido->estado === 'en_juego') {
                    $estadoCierre = 'En juego';
                    } else {
                    $inicio = \Carbon\Carbon::parse($partido->fecha_hora);
                    $estadoCierre = now()->diffForHumans($inicio, [
                    'syntax' => \Carbon\Carbon::DIFF_ABSOLUTE,
                    'short' => true,
                    'parts' => 2,
                    ]);
                    }
                    @endphp

                    <tr class="fila-partido">

                        {{-- FASE / GRUPO --}}
                        <td class="col-fase">
                            @if($partido->grupo)
                            <small>Grupo {{ $partido->grupo }}</small>
                            @else
                            {{ $partido->fase }}
                            @endif
                        </td>

                        {{-- FECHA --}}
                        <td class="col-fecha">
                            {{ \Carbon\Carbon::parse($partido->fecha_hora)->format('d/m/Y') }}
                            <strong>{{ \Carbon\Carbon::parse($partido->fecha_hora)->format('H:i') }}</strong>
                        </td>

                        {{-- PARTIDO --}}
                        <td class="col-partido">
                            <div class="partido-flex">
                                <div class="equipo">
                                    <img src="{{ $partido->equipo_local_crest_url }}" alt="">
                                    <span>{{ $partido->equipo_local_shortname }}</span>
                                </div>

                                <strong class="vs">vs</strong>

                                <div class="equipo">
                                    <img src="{{ $partido->equipo_visitante_crest_url }}" alt="">
                                    <span>{{ $partido->equipo_visitante_shortname }}</span>
                                </div>
                            </div>
                        </td>

                        {{-- MI PRONÓSTICO --}}
                        <td class="col-pronostico">
                            @if($pronostico)
                            {{ $pronostico->goles_local_pronosticados }}
                            <span class="guion">-</span>
                            {{ $pronostico->goles_visitante_pronosticados }}
                            @else
                            <span>Pendiente</span>
                            @endif
                        </td>

                        {{-- 1X2 --}}
                        <td class="col-pronostico">
                            {{ $signo }}
                        </td>

                        {{-- HORAS HASTA CIERRE --}}
                        <td class="col-fecha">
                            {{ $estadoCierre }}
                        </td>
                        {{-- MARCADOR --}}
                        <td class="col-fecha">
                            @if($partido->estado !== 'programado')
                            {{ $partido->goles_local }} - {{ $partido->goles_visitante }}
                            @else
                            ---
                            @endif
                        </td>

                        {{-- ENLACE A DETALLE --}}
                        <td class="col-pronostico">
                            <a href="{{ route('porras.partido', [
                                    'idPorra'   => $porra->id_porra,
                                    'idPartido' => $partido->id_partido
                                ]) }}">
                                Acceder
                            </a>
                        </td>

                    </tr>
                    @endforeach
            </tbody>
        </table>

        @endif
    </section>

    {{-- ===================================================== --}}
    {{-- BARRA LATERAL --}}
    {{-- ===================================================== --}}
    <aside class="sidebar">

        <h2>Acciones</h2>

        <p>
            <a href="{{ route('porras.pronosticos', $porra->id_porra) }}">
                Enviar o modificar pronósticos
            </a>
        </p>

        <p>
            <a href="{{ route('porras.mis_pronosticos', $porra->id_porra) }}">
                Ver mis pronósticos
            </a>
        </p>

        <p>
            <a href="{{ route('porras.campeones', $porra->id_porra) }}">
                Enviar o modificar campeones
            </a>
        </p>

        <p>
            <a href="{{ route('porras.campeones.participantes', $porra->id_porra) }}">
                Ver pronósticos de campeones del resto de participantes
            </a>
        </p>

        <p>
            <a href="{{ route('tabla.partidos', $porra->id_porra) }}">
                Ver últimos partidos
            </a>
        </p>

        <hr>

        <h2>Otras porras</h2>

        <p>
            <a href="{{ route('porras.disponibles') }}">
                Ver porras disponibles
            </a>
        </p>

        <p>
            <a href="{{ route('porras.mis') }}">
                Volver a Mis porras
            </a>
        </p>

        <hr>

        @if($esAdmin)

        <form method="POST" action="{{ route('admin.importar-mundial', ['porra' => $porra->id_porra]) }}">
            @csrf
            <button
                type="submit"
                class="btn-admin"
                onclick="return confirm('¿Actualizar partidos del Mundial desde la API?')">
                DESCARGAR PARTIDOS MUNDIAL DE LA API
            </button>
        </form>

        <hr>

        <form method="POST" action="{{ route('admin.recalcular-clasificacion', ['porra' => $porra->id_porra]) }}">
            @csrf
            <button
                type="submit"
                class="btn-admin"
                onclick="return confirm('¿Recalcular clasificación de la porra?')">
                RECALCULAR CLASIFICACIÓN
            </button>
        </form>
        <hr>


        <form method="POST" action="{{ route('porras.destroy', ['id' => $porra->id_porra]) }}">
            @csrf
            @method('DELETE')

            <button
                type="submit"
                class="btn-admin danger"
                onclick="return confirm('ATENCIÓN: esta acción eliminará la porra y TODOS los pronósticos asociados.\n\nEsta operación es IRREVERSIBLE.\n\n¿Deseas continuar?')">
                ELIMINAR PORRA
            </button>
        </form>

        @endif

    </aside>

</main>
@endsection