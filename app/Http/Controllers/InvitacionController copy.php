<?php

namespace App\Http\Controllers;

use App\Models\Porra;
use App\Models\Participacion;
use App\Models\Usuario;
use App\Models\Invitacion;
use Illuminate\Http\Request;

/**
 * InvitacionController
 *
 * Pantalla 8.15: Invitar a usuarios a una porra (por usuario o por email).
 * Reglas:
 * - Solo puede invitar quien sea admin de la porra (participaciones.es_admin = 1). [1](https://moleaer-my.sharepoint.com/personal/miguel_moleaer_com/Documents/Microsoft%20Copilot%20Chat%20Files/CREACION%20BASE%20DE%20DATOS.txt)
 * - Se guardan invitaciones con estado 'pendiente' (tabla invitaciones). [1](https://moleaer-my.sharepoint.com/personal/miguel_moleaer_com/Documents/Microsoft%20Copilot%20Chat%20Files/CREACION%20BASE%20DE%20DATOS.txt)
 *
 * IMPORTANTE EN TU PROYECTO:
 * - La autenticación es manual por sesión: session('usuario')
 * - NO usar Auth::user() / Auth::id() (Laravel Auth), porque en tu proyecto no es la fuente de verdad.
 */
class InvitacionController extends Controller
{
    /**
     * GET /porras/{id}/invitar
     */
    public function create($id)
    {
        $usuarioSesion = session('usuario');
        if (!$usuarioSesion) {
            return redirect()->route('login');
        }

        $porra = Porra::with(['competicion'])->findOrFail($id);

        // Seguridad: solo el admin de la porra puede acceder a invitar
        $this->authorizeAdminOfPorra($porra->id_porra, $usuarioSesion->id_usuario);

        $invitaciones = Invitacion::where('id_porra', $porra->id_porra)
            ->orderByDesc('fecha_envio')
            ->get();

        return view('porras.invitar', compact('porra', 'invitaciones'));
    }

    /**
     * POST /porras/{id}/invitar
     */
    public function store(Request $request, $id)
    {
        $usuarioSesion = session('usuario');
        if (!$usuarioSesion) {
            return redirect()->route('login');
        }

        $porra = Porra::findOrFail($id);

        // Seguridad: solo admin
        $this->authorizeAdminOfPorra($porra->id_porra, $usuarioSesion->id_usuario);

        // Reglas básicas: porra activa y (si hay) no superar max participantes
        if ($porra->estado !== 'activa') {
            return back()->withErrors(['general' => 'La porra no está activa. No se pueden enviar invitaciones.']);
        }

        if (!is_null($porra->max_participantes)) {
            $numParticipantes = Participacion::where('id_porra', $porra->id_porra)->count();
            if ($numParticipantes >= $porra->max_participantes) {
                return back()->withErrors(['general' => 'La porra ha alcanzado el máximo de participantes.']);
            }
        }

        // Validación común: el tipo debe ser usuario o email
        $request->validate([
            'tipo' => 'required|in:usuario,email',
        ]);

        // ==========================
        // INVITAR POR USUARIO
        // ==========================
        if ($request->tipo === 'usuario') {

            $data = $request->validate([
                'usuario_destino' => 'required|string|max:50',
            ]);

            // Evitar que el administrador se invite a sí mismo
            if ($data['usuario_destino'] === $usuarioSesion->nombre_usuario) {
                return back()->withErrors([
                    'usuario_destino' => 'No puedes invitarte a ti mismo a una porra que ya administras.',
                ]);
            }

            // Comprobación: ¿existe ese usuario?
            $usuarioDestino = Usuario::where('nombre_usuario', $data['usuario_destino'])->first();
            if (!$usuarioDestino) {
                return back()->withErrors(['usuario_destino' => 'No existe un usuario con ese nombre.']);
            }

            // (Recomendado) Si ya participa, no invitar
            $yaParticipa = Participacion::where('id_porra', $porra->id_porra)
                ->where('id_usuario', $usuarioDestino->id_usuario)
                ->exists();

            if ($yaParticipa) {
                return back()->withErrors([
                    'usuario_destino' => 'Ese usuario ya participa en la porra. No es necesario invitarlo.',
                ]);
            }

            // Bloqueo duplicado cruzado (usuario/email) en la misma porra
            $duplicada = Invitacion::where('id_porra', $porra->id_porra)
                ->where('estado', 'pendiente')
                ->where(function ($q) use ($usuarioDestino) {
                    $q->where('usuario_destino', $usuarioDestino->nombre_usuario)
                      ->orWhere('email_destino', $usuarioDestino->correo_electronico);
                })
                ->exists();

            if ($duplicada) {
                return back()->withErrors([
                    'usuario_destino' => 'Ese usuario ya tiene una invitación pendiente (por usuario o por email).',
                ]);
            }

            Invitacion::create([
                'id_porra' => $porra->id_porra,
                'id_usuario_invitador' => $usuarioSesion->id_usuario, // ✅ sesión
                'usuario_destino' => $usuarioDestino->nombre_usuario,
                'email_destino' => null,
                'estado' => 'pendiente',
            ]);

            return back()->with('success', 'Invitación enviada al usuario correctamente.');
        }

        // ==========================
        // INVITAR POR EMAIL
        // ==========================
        $data = $request->validate([
            'email_destino' => 'required|email|max:100',
        ]);

        // Evitar que el administrador se invite a sí mismo por email
        if ($data['email_destino'] === $usuarioSesion->correo_electronico) {
            return back()->withErrors([
                'email_destino' => 'No puedes invitarte a ti mismo a una porra que ya administras.',
            ]);
        }

        // Si el email corresponde a un usuario existente, obtenemos también su nombre de usuario
        $usuarioDestino = Usuario::where('correo_electronico', $data['email_destino'])->first();

        // Si el email corresponde a un usuario que ya participa, no invitar
        if ($usuarioDestino) {
            $yaParticipa = Participacion::where('id_porra', $porra->id_porra)
                ->where('id_usuario', $usuarioDestino->id_usuario)
                ->exists();

            if ($yaParticipa) {
                return back()->withErrors([
                    'email_destino' => 'Ese usuario ya participa en la porra. No es necesario invitarlo.',
                ]);
            }
        }

        // Bloqueo duplicado cruzado (si hay usuario, por usuario/email; si no, por email)
        $duplicada = Invitacion::where('id_porra', $porra->id_porra)
            ->where('estado', 'pendiente')
            ->where(function ($q) use ($data, $usuarioDestino) {
                $q->where('email_destino', $data['email_destino']);

                if ($usuarioDestino) {
                    $q->orWhere('usuario_destino', $usuarioDestino->nombre_usuario)
                      ->orWhere('email_destino', $usuarioDestino->correo_electronico);
                }
            })
            ->exists();

        if ($duplicada) {
            return back()->withErrors([
                'email_destino' => 'Ese usuario ya tiene una invitación pendiente (por usuario o por email).',
            ]);
        }

        Invitacion::create([
            'id_porra' => $porra->id_porra,
            'id_usuario_invitador' => $usuarioSesion->id_usuario, // ✅ sesión
            'usuario_destino' => null,
            'email_destino' => $data['email_destino'],
            'estado' => 'pendiente',
        ]);

        return back()->with('success', 'Invitación enviada por correo correctamente.');
    }

    /**
     * Comprueba que el usuario es admin de la porra (participaciones.es_admin = 1). [1](https://moleaer-my.sharepoint.com/personal/miguel_moleaer_com/Documents/Microsoft%20Copilot%20Chat%20Files/CREACION%20BASE%20DE%20DATOS.txt)
     * Importante: aquí NO usamos Auth::id(), usamos el id_usuario de sesión.
     */
    private function authorizeAdminOfPorra(int $idPorra, int $idUsuario): void
    {
        $esAdmin = Participacion::where('id_porra', $idPorra)
            ->where('id_usuario', $idUsuario)
            ->where('es_admin', 1)
            ->exists();

        abort_unless($esAdmin, 403, 'No tienes permisos para invitar participantes en esta porra.');
    }
}