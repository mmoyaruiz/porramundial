@extends('layouts.app')

@section('title', 'Invitar participantes')

@section('content')
<main class="home-layout">

    {{-- Columna izquierda: info de la porra --}}
    <section class="hero">
        <h1>Invitar participantes</h1>

        <div class="hero-highlight">
            <strong>Porra:</strong> {{ $porra->nombre }}<br>
            <strong>Competición:</strong> {{ $porra->competicion?->nombre }}<br>
            <strong>Estado:</strong> {{ $porra->estado }}<br>
            <strong>Tipo:</strong> {{ $porra->es_publica ? 'Pública' : 'Privada' }}<br>
            <strong>Máx. participantes:</strong> {{ $porra->max_participantes ?? 'Sin límite' }}
        </div>

        <h2 style="margin-top: 20px;">Invitaciones enviadas</h2>

        @if(session('success'))
            <div class="alert-success">{{ session('success') }}</div>
        @endif

        @if($errors->has('general'))
            <div class="alert-error">{{ $errors->first('general') }}</div>
        @endif

        <table class="table">
            <thead>
            <tr>
                <th>Destino</th>
                <th>Estado</th>
                <th>Fecha envío</th>
            </tr>
            </thead>
            <tbody>
            @forelse($invitaciones as $inv)
                <tr>
                    <td>
                        {{ $inv->usuario_destino ?? $inv->email_destino }}
                    </td>
                    <td>{{ $inv->estado }}</td>
                    <td>{{ $inv->fecha_envio }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No hay invitaciones enviadas todavía.</td></tr>
            @endforelse
            </tbody>
        </table>

        <p style="margin-top: 14px;">
            <a href="{{ route('porras.show', $porra->id_porra) }}">← Volver a la porra</a>
        </p>
    </section>

    {{-- Columna derecha: dos formularios (usuario / email) --}}
    <aside class="sidebar">
        <h2>Invitar por usuario</h2>

        <form action="{{ route('porras.invitar.store', $porra->id_porra) }}" method="POST">
            @csrf
            <input type="hidden" name="tipo" value="usuario">

            <div class="form-group">
                <label for="usuario_destino">Nombre de usuario</label>
                <input id="usuario_destino" name="usuario_destino" type="text"
                       value="{{ old('usuario_destino') }}" maxlength="50" required>
                @error('usuario_destino')
                    <div class="alert-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-block">Enviar invitación</button>
        </form>

        <hr style="margin: 18px 0;">

        <h2>Invitar por correo</h2>

        <form action="{{ route('porras.invitar.store', $porra->id_porra) }}" method="POST">
            @csrf
            <input type="hidden" name="tipo" value="email">

            <div class="form-group">
                <label for="email_destino">Correo electrónico</label>
                <input id="email_destino" name="email_destino" type="email"
                       value="{{ old('email_destino') }}" maxlength="100" required>
                @error('email_destino')
                    <div class="alert-error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-block">Enviar invitación</button>
        </form>
    </aside>

</main>
@endsection
