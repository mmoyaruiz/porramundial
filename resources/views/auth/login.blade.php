@extends('layouts.app')

@section('title', 'Iniciar sesión')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/auth.css') }}">
@endpush

@section('content')
<main>

    <section class="card">
        <h1>Iniciar sesión</h1>

        <p class="description">
            Accede a tu cuenta para gestionar tus porras y pronósticos.
        </p>




        @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
        @endif

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


        


        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="form-group">
                <label for="correo_electronico">Correo electrónico</label>
                <input
                    type="email"
                    id="correo_electronico"
                    name="correo_electronico"
                    required>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required>
            </div>

            <button type="submit" class="btn btn-primary">
                Entrar
            </button>
        </form>

        <div class="extra-links">
            ¿No tienes cuenta?
            <a href="{{ route('register') }}">Regístrate aquí</a>
        </div>
    </section>

</main>
@endsection