<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Controlador AuthController
 *
 * Gestiona la autenticación básica del sistema:
 * - Inicio de sesión.
 * - Registro de usuarios.
 * - Cierre de sesión.
 *
 * La aplicación utiliza autenticación manual mediante sesión
 *
 * Pantallas relacionadas (ERS):
 * - Login
 * - Registro
 * - Logout
 */
class AuthController extends Controller
{
    /**
     * Muestra el formulario de inicio de sesión.
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Procesa el inicio de sesión del usuario.
     *
     * Valida credenciales y, si son correctas,
     * guarda el usuario en sesión y redirige al dashboard.
     */
    public function login(Request $request)
    {
        $request->validate([
            'correo_electronico' => 'required|email',
            'password' => 'required',
        ]);

        // Buscar usuario por correo
        $usuario = Usuario::where('correo_electronico', $request->correo_electronico)->first();

        // Verificar credenciales
        if (!$usuario || !password_verify($request->password, $usuario->password_hash)) {
            return back()->withErrors([
                'login' => 'Credenciales incorrectas',
            ]);
        }

        // Guardar usuario en sesión (autenticación manual)
        session(['usuario' => $usuario]);

        // Redirigir a la pantalla principal tras login
        return redirect()->route('dashboard');
    }

    /**
     * Muestra el formulario de registro de usuario.
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Procesa el registro de un nuevo usuario.
     *
     * Valida los datos y guarda el usuario en la base de datos
     * con la contraseña cifrada.
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

        return redirect()
            ->route('login')
            ->with('success', 'Usuario registrado correctamente. Ya puedes iniciar sesión.');
    }

    /**
     * Cierra la sesión del usuario.
     *
     * Elimina la sesión activa y regenera el token CSRF.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
