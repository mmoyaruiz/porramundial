@extends('layouts.app')

@section('title', 'Registrarse')

@push('styles')

    {{-- CARGA DE CSS Y JS CON VITE --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Estilos adicionales opcionales por vista --}}
    @stack('styles')
@endpush

@section('content')
<main class="auth">

    <section class="card">
        <h1>Crear cuenta</h1>

        <p class="description">
            Regístrate para crear porras, unirte a porras existentes y enviar tus pronósticos.
        </p>

        {{-- Mensaje de éxito (por ejemplo, tras registrar y redirigir al login) --}}
        @if (session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        {{-- Errores globales de validación --}}
        @if ($errors->any())
            <div class="alert-error">
                <strong>Revisa el formulario:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('register') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="nombre_usuario">Nombre de usuario</label>
                <input
                    type="text"
                    id="nombre_usuario"
                    name="nombre_usuario"
                    placeholder="Ej: miguelmoya"
                    value="{{ old('nombre_usuario') }}"
                    required
                    maxlength="50"
                >
            </div>

            <div class="form-group">
                <label for="correo_electronico">Correo electrónico</label>
                <input
                    type="email"
                    id="correo_electronico"
                    name="correo_electronico"
                    placeholder="tucorreo@ejemplo.com"
                    value="{{ old('correo_electronico') }}"
                    required
                    maxlength="100"
                >
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Mínimo 6 caracteres"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password_confirmation">Repite la contraseña</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    placeholder="Repite la contraseña"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Registrarme
            </button>
        </form>

        <div class="extra-links">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}">Inicia sesión</a>.
        </div>


    </section>

</main>
@endsection
