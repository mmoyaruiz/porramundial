@extends('layouts.app')

@section('title', 'Enviar pronósticos')

@section('content')
<main class="home-layout">

    <section class="hero">
        <h1>Enviar pronósticos</h1>
        <p><strong>Porra:</strong> {{ $porra->nombre }}</p>
        <ul>
            <li>Los pronósticos de cada partido se podrán guardar/modificar hasta el momento justo en que empiece el partido.</li>
            <li>No se pueden poner más de 4 goles locales o visitantes en un partido.</li>
            <li>Si los goles reales son 4 o más, se considerará acierto si el pronóstico de goles del participante es 4.</li>
        </ul>

        <form action="{{ route('pronosticos.store', $porra->id_porra) }}" method="POST">
            @csrf

            <table class="table">
                <thead>
                    <tr>
                        <th>Fase</th>
                        <th>Fecha</th>
                        <th>Partido</th>
                        <th>Pronóstico</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($partidos as $partido)

                        @if($partido->estado === 'programado')

                        <tr class="fila-partido">

                            {{-- FASE --}}
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

                            {{-- PRONÓSTICO --}}
                            <td class="col-pronostico">

                                <input type="number"
                                    name="pronosticos[{{ $partido->id_partido }}][local]"
                                    min="0" max="4"                                    value="{{ old(
                                    'pronosticos.' . $partido->id_partido . '.local',
                                    isset($pronosticos[$partido->id_partido])
                                    ? $pronosticos[$partido->id_partido]->goles_local_pronosticados
                                    : ''
                                    ) }}">

                                <span class="guion">-</span>

                                <input type="number"
                                    name="pronosticos[{{ $partido->id_partido }}][visitante]"
                                    min="0" max="4"
                                    value="{{ old(
                                    'pronosticos.' . $partido->id_partido . '.visitante',
                                    isset($pronosticos[$partido->id_partido])
                                    ? $pronosticos[$partido->id_partido]->goles_visitante_pronosticados
                                    : ''
                                    ) }}">

                            </td>

                        </tr>
                        @endif
                    @endforeach
                </tbody>

            </table>
            <br>
            

            <button type="submit" class="btn btn-primary">Guardar pronósticos</button>
        </form>
    </section>


    <aside class="sidebar">
        <h2>Acciones</h2>
        <p><a href="{{ route('porras.show', $porra->id_porra) }}">← Volver a la porra</a></p>
        <p><a href="{{ route('porras.mis') }}">Mis porras</a></p>
    </aside>
</main>
@endsection