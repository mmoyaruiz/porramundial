@extends('layouts.app')
@section('title','Crear nueva porra')

@section('content')
<main>
    <section class="card">
        <h1>Crear nueva porra</h1>

        @if ($errors->any())
            <div class="alert-error">
                <strong>Revisa el formulario:</strong>
                <ul>
                    @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('porras.store') }}">
            @csrf

            <h3>1) Datos generales</h3>

            <div class="form-group">
                <label for="nombre">Nombre de la porra</label>
                <input id="nombre" name="nombre" type="text" value="{{ old('nombre') }}" required maxlength="100">
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion">{{ old('descripcion') }}</textarea>
            </div>

            <div class="form-group">
                <label for="id_competicion">Competición</label>
                <select id="id_competicion" name="id_competicion" required>
                    <option value="">-- Selecciona --</option>
                    @foreach($competiciones as $c)
                        <option value="{{ $c->id_competicion }}" @selected(old('id_competicion')==$c->id_competicion)>
                            {{ $c->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="es_publica">Tipo</label>
                <select id="es_publica" name="es_publica" required>
                    <option value="1" @selected(old('es_publica')==='1')>Pública</option>
                    <option value="0" @selected(old('es_publica')==='0')>Privada</option>
                </select>
            </div>

            <h3>2) Reglas de puntuación</h3>

            <div class="form-group">
                <label for="puntos_ganador">Puntos por acertar ganador (1X2)</label>
                <input id="puntos_ganador" name="puntos_ganador" type="number" min="0" value="{{ old('puntos_ganador', 1) }}" required>
            </div>

            <div class="form-group">
                <label for="puntos_marcador">Puntos por acertar marcador exacto</label>
                <input id="puntos_marcador" name="puntos_marcador" type="number" min="0" value="{{ old('puntos_marcador', 3) }}" required>
            </div>

            <div class="form-group">
                <label for="puntos_campeon_grupo">Puntos por acertar campeón de grupo</label>
                <input id="puntos_campeon_grupo" name="puntos_campeon_grupo" type="number" min="0" value="{{ old('puntos_campeon_grupo', 0) }}" required>
            </div>

            <div class="form-group">
                <label for="puntos_ganador_torneo">Puntos por acertar ganador del torneo</label>
                <input id="puntos_ganador_torneo" name="puntos_ganador_torneo" type="number" min="0" value="{{ old('puntos_ganador_torneo', 0) }}" required>
            </div>

            <h3>3) Límites</h3>

            <div class="form-group">
                <label for="max_participantes">Número máximo de participantes (opcional)</label>
                <input id="max_participantes" name="max_participantes" type="number" min="2" value="{{ old('max_participantes') }}">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Crear porra</button>
        </form>
    </section>
</main>
@endsection
