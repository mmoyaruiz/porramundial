<?php

namespace App\Http\Controllers;

use App\Models\Porra;
use App\Models\Participacion;
use App\Models\Usuario;
use App\Models\Invitacion;
use Illuminate\Http\Request;


/**
 * Controlador InvitacionController
 *
 * Invitar participantes a una porra.
 *
 * Permite invitar:
 * - Por nombre de usuario.
 * - Por correo electrónico.
 *
 * Reglas principales
 * - Solo puede invitar el administrador de la porra (participaciones.es_admin = 1).
 * - Las invitaciones se guardan con estado "pendiente".
 * - Se evita invitar a usuarios que ya participan o duplicar invitaciones pendientes.
 *
 */
class InvitacionController extends Controller
{
    /**
     * Muestra la información de la porra y el listado de invitaciones.
     */
    public function create($id)
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $porra = Porra::with(['competicion'])->findOrFail($id);

        // Solo el admin de la porra puede acceder a invitar
        $this->authorizeAdminOfPorra($porra->id_porra, $usuario->id_usuario);

        $invitaciones = Invitacion::where('id_porra', $porra->id_porra)
            ->orderByDesc('fecha_envio')
            ->get();

        return view('porras.invitar', compact('porra', 'invitaciones'));
    }

    /**
     * Envía una invitación (por usuario o por email) y la guarda como "pendiente".
     */
    public function store(Request $request, $id)
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $porra = Porra::findOrFail($id);

        // Solo el admin de la porra puede invitar
        $this->authorizeAdminOfPorra($porra->id_porra, $usuario->id_usuario);

        // Solo se invita si la porra está activa
        if ($porra->estado !== 'activa') {
            return back()->withErrors(['general' => 'La porra no está activa. No se pueden enviar invitaciones.']);
        }

        // Respeto del máximo de participantes si está configurado
        if (!is_null($porra->max_participantes)) {
            $numParticipantes = Participacion::where('id_porra', $porra->id_porra)->count();
            if ($numParticipantes >= $porra->max_participantes) {
                return back()->withErrors(['general' => 'La porra ha alcanzado el máximo de participantes.']);
            }
        }

        // Tipo de invitación
        $request->validate([
            'tipo' => 'required|in:usuario,email',
        ]);

        /*
         * ==========================================================
         * INVITACIÓN POR NOMBRE DE USUARIO
         * ==========================================================
         */
        if ($request->tipo === 'usuario') {

            $data = $request->validate([
                'usuario_destino' => 'required|string|max:50',
            ]);

            // Evitar autoinvitación
            if ($data['usuario_destino'] === $usuario->nombre_usuario) {
                return back()->withErrors([
                    'usuario_destino' => 'No puedes invitarte a ti mismo a una porra que ya administras.',
                ]);
            }

            // Debe existir el usuario destino
            $usuarioDestino = Usuario::where('nombre_usuario', $data['usuario_destino'])->first();
            if (!$usuarioDestino) {
                return back()->withErrors(['usuario_destino' => 'No existe un usuario con ese nombre.']);
            }

            // Si ya participa, no invitamos
            $yaParticipa = Participacion::where('id_porra', $porra->id_porra)
                ->where('id_usuario', $usuarioDestino->id_usuario)
                ->exists();

            if ($yaParticipa) {
                return back()->withErrors([
                    'usuario_destino' => 'Ese usuario ya participa en la porra. No es necesario invitarlo.',
                ]);
            }

            // Evitar duplicado de invitación pendiente (por usuario o por email)
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
                'id_usuario_invitador' => $usuario->id_usuario,
                'usuario_destino' => $usuarioDestino->nombre_usuario,
                'email_destino' => null,
                'estado' => 'pendiente',
            ]);

            return back()->with('success', 'Invitación enviada al usuario correctamente.');
        }

        /*
         * ==========================================================
         * INVITACIÓN POR EMAIL
         * ==========================================================
         */
        $data = $request->validate([
            'email_destino' => 'required|email|max:100',
        ]);

        // Evitar autoinvitación por email
        if ($data['email_destino'] === $usuario->correo_electronico) {
            return back()->withErrors([
                'email_destino' => 'No puedes invitarte a ti mismo a una porra que ya administras.',
            ]);
        }

        // Si el email pertenece a un usuario existente, lo usamos para comprobar participación/duplicados
        $usuarioDestino = Usuario::where('correo_electronico', $data['email_destino'])->first();

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

        // Evitar duplicado de invitación pendiente
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
            'id_usuario_invitador' => $usuario->id_usuario,
            'usuario_destino' => null,
            'email_destino' => $data['email_destino'],
            'estado' => 'pendiente',
        ]);

        return back()->with('success', 'Invitación enviada por correo correctamente.');
    }

    /**
     * Comprueba que el usuario sea administrador de la porra.
     * Si no lo es, se devuelve 403.
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