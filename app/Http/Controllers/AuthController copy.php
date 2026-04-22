<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Controlador de Autenticación.
 * - Registro (RF1)
 * - Login (RF2)
 * - Logout
 *
 */
class AuthController extends Controller
{
    /**
     * Muestra formulario login.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Procesa login.
     */
    
public function login(Request $request)
{
    $request->validate([
        'correo_electronico' => 'required|email',
        'password' => 'required',
    ]);

    // 1️⃣ Buscar el usuario en BD
    $usuario = Usuario::where('correo_electronico', $request->correo_electronico)->first();

    // 2️⃣ Verificar credenciales
    if (!$usuario || !password_verify($request->password, $usuario->password_hash)) {
        return back()->withErrors([
            'login' => 'Credenciales incorrectas'
        ]);
    }

    // 3️⃣ Guardar usuario en sesión (CLAVE)
    session(['usuario' => $usuario]);

    // 4️⃣ Redirigir al dashboard (8.5)
    return redirect()->route('dashboard');
}

    /**
     * Muestra formulario de registro.
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Procesa registro.
     */
    public function register(Request $request)
    {
        $request->validate([
            'nombre_usuario' => 'required|string|max:50|unique:usuarios,nombre_usuario',
            'correo_electronico' => 'required|email|max:100|unique:usuarios,correo_electronico',
            'password' => 'required|min:6|confirmed',
        ]);

        Usuario::create([
            'nombre_usuario' => $request->nombre_usuario,
            'correo_electronico' => $request->correo_electronico,
            'password_hash' => Hash::make($request->password),
            'es_activo' => 1,
        ]);

        // Opcional: redirigir al login con mensaje
        return redirect()
            ->route('login')
            ->with('success', 'Usuario registrado correctamente. Ya puedes iniciar sesión.');
    }

    /**
     * Cierra sesión.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        // Invalidar sesión y regenerar token CSRF
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
