@extends('layouts.app')

@section('title', 'Enviar o modificar campeones')

@section('content')
<main class="home-layout">
    <section class="hero">
        <h1>Enviar o modificar campeones</h1>
        <p><strong>Porra:</strong> {{ $porra->nombre }}</p>

        @if(session('warning'))
        <div class="alert-warning">
            {{ session('warning') }}
        </div>
        @endif

        @if($competicionComenzada)
        <div class="alert-warning">
            La competición ha comenzado, por lo que no es posible modificar los pronósticos de campeones.
        </div>
        @endif

        @if($errors->any())
        <div class="alert-error">
            <strong>Revisa el formulario:</strong>
            <ul>
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('porras.campeones.update', $porra->id_porra) }}" method="POST">
            @csrf

            <h2>Campeón absoluto de la competición</h2>

            @if($competicionComenzada)
            <p>
                <strong>Campeón pronosticado:</strong>
                {{ $campeonCompeticion->equipo_pronosticado ?? '—' }}
            </p>
            @else
            <div class="form-group">
                <label for="campeon_competicion">Selecciona el campeón del torneo</label>
                <select id="campeon_competicion" name="campeon_competicion">
                    <option value="">-- Selecciona --</option>
                    @foreach($equiposCompeticion as $equipo)
                    <option value="{{ $equipo }}"
                        @selected(old('campeon_competicion', $campeonCompeticion->equipo_pronosticado ?? '') === $equipo)>
                        {{ $equipo }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <hr>

            <h2>Campeones de grupo</h2>
            <p>Selecciona un campeón por cada grupo (si aplica).</p>

            @foreach($equiposPorGrupo as $grupo => $equipos)
            <div class="form-group">
                <label for="grupo_{{ $grupo }}">Grupo {{ $grupo }}</label>
                @if($competicionComenzada)
                <p>
                    <strong>Campeón pronosticado:</strong>
                    {{ $campeonesGrupo[$grupo]->equipo_pronosticado ?? '—' }}
                </p>
                @else

                <select id="grupo_{{ $grupo }}" name="campeones_grupo[{{ $grupo }}]">
                    <option value="">-- Selecciona --</option>
                    @foreach($equipos as $equipo)
                    <option value="{{ $equipo }}"
                        @selected(old("campeones_grupo.$grupo", $campeonesGrupo[$grupo]->equipo_pronosticado ?? '') === $equipo)>
                        {{ $equipo }}
                    </option>
                    @endforeach
                </select>
                @endif
            </div>
            @endforeach

            @if(!$competicionComenzada)
                <button type="submit" class="btn btn-primary btn-block">Guardar campeones</button>
            @endif
            
        </form>
    </section>

    <aside class="sidebar">
        <h2>Acciones</h2>
        <p><a href="{{ route('porras.show', $porra->id_porra) }}">Volver a la porra</a></p>
        <p><a href="{{ route('porras.pronosticos', $porra->id_porra) }}">Enviar/Modificar pronósticos</a></p>
    </aside>
</main>
@endsection