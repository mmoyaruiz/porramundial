
@extends('layouts.app')

@section('title', 'Iniciar sesión')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

<!-- CONTENIDO PRINCIPAL -->
<main>
    <section class="card">
        <h1>Iniciar sesión</h1>

        <p class="description">
            Accede a tu cuenta para gestionar tus porras, enviar pronósticos
            y consultar las clasificaciones en tiempo real.
        </p>

        <!-- FORMULARIO DE LOGIN -->
        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="correo_electronico">Correo electrónico</label>
                <input
                    type="email"
                    id="correo_electronico"
                    name="correo_electronico"
                    placeholder="tucorreo@ejemplo.com"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Introduce tu contraseña"
                    required
                >

                <div class="form-helper">
                    <a href="#">He olvidado mi contraseña</a>
                </div>
            </div>

            <!-- BOTÓN SUBMIT -->
            <button type="submit" class="btn btn-primary">
                Entrar
            </button>
        </form>

        <div class="extra-links">
            ¿Aún no tienes cuenta?
            <a href="{{ route('register') }}">Regístrate aquí</a>.
        </div>

        <div class="back-home">
            <a href="{{ route('home') }}">← Volver a la página de inicio</a>
        </div>
    </section>
</main>



