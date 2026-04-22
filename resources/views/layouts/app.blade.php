<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'PORRAMUNDIAL.COM')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- CARGA DE CSS Y JS CON VITE --}}
    @vite(['resources/css/app.css', 'resources/js/app.js']) 
</head>

<body>

    <header>
        <div class="logo">
            <div class="logo-badge">PM</div>
            <span>PORRAMUNDIAL.COM</span>
        </div>

        <nav>



            @if(session()->has('usuario'))
            <span><strong>Bienvenido {{ session('usuario')->nombre_usuario }}</strong></span>
            <a href="{{ route('dashboard') }}">Panel de Control</a>
            @else
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('register') }}">Registro</a>
            @endif
            <a href="{{ route('home') }}">Inicio</a>
            <a href="{{ route('help.index') }}">Ayuda</a>

            <!-- El logout solo admite metodo POST, por lo que no puedo hacerlo con un 'anchor' normal -->
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Salir</a>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>





        </nav>
    </header>

    <main>
        @yield('content')
    </main>

    <footer>
        © {{ date('Y') }} PORRAMUNDIAL.COM ·
        <a href="#">Aviso legal</a> ·
        <a href="#">Política de privacidad</a> ·
        <a href="#">Política de cookies</a>
    </footer>

</body>

</html>