@extends('layouts.app')

@section('title', 'Tabla de resultados')

@section('content')
<main class="home-layout">

    <section class="hero">
        <h1>Tabla de resultados</h1>
        <p>
            Últimos partidos cerrados y en juego de la porra
            <strong>{{ $porra->nombre }}</strong>.
        </p>

        <h3>Pronósticos por participante (se muestran los últimos 10 partidos)</h3>

        <table class="table">
            <thead>
                <tr>
                    <th>Participante</th>

                    @foreach($partidos as $partido)
                        <th>
                            
<a href="{{ route('porras.partido', ['idPorra' => $porra->id_porra, 'idPartido' => $partido->id_partido]) }}"
       class="partido-link">
        {{ $partido->equipo_local_tla }}<br>
        vs<br>
        {{ $partido->equipo_visitante_tla }}
    </a>

                        </th>
                    @endforeach
                </tr>

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
                <tr>
                    <th>Marcador</th>
                    @foreach($partidos as $partido)
                        <th>
                            @if($partido->estado === 'programado')
                                {{ \Carbon\Carbon::parse($partido->fecha_hora)->format('d/m/Y H:i') }}
                            @else
                                {{ $partido->goles_local }} - {{ $partido->goles_visitante }}<br>   
                                
                            
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
            @foreach($participantes as $participante)
                <tr>
                    <td><strong>{{ $participante->nombre_usuario }}</strong></td>

                    @foreach($partidos as $partido)
                        @php
                            $p = $pronosticos[$participante->id_usuario][$partido->id_partido] ?? null;

                            $acierto = false;
                            if ($p && $partido->goles_local !== null) {
                                $acierto =
                                    $p->goles_local_pronosticados == $partido->goles_local &&
                                    $p->goles_visitante_pronosticados == $partido->goles_visitante;
                            }
                        @endphp

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

    <aside class=sidebar>
        <h2>Acciones</h2>

        <p><a href="{{ route('porras.show', $porra->id_porra) }}">Volver a la porra</a></p>
    
           
       
    </aside>

</main>
@endsection