<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>PORRAMUNDIAL.COM - Iniciar sesión</title>

    <!-- Metadatos básicos -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
          content="Pantalla de inicio de sesión de PORRAMUNDIAL.COM, la aplicación web para gestionar porras de fútbol.">

    <!-- CSS externo -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">

</head>
<body>

<!-- CABECERA -->
<header>
    <div class="logo">
        <div class="logo-badge">PM</div>
        PORRAMUNDIAL.COM
    </div>

    <nav>
        <a href="{{ route('home') }}">Inicio</a>
        <a href="{{ route('register') }}">Registrarse</a>
        <a href="#">Porras públicas</a>
    </nav>
</header>

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

<!-- PIE DE PÁGINA -->
<footer>
    © 2025 PORRAMUNDIAL.COM ·
    <a href="#">Aviso legal</a> ·
    <a href="#">Política de privacidad</a> ·
    <a href="#">Política de cookies</a>
</footer>

</body>
</html>
``




