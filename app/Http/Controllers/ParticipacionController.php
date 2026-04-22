<?php

namespace App\Http\Controll;

use App\Models\Participacion;
use App\Models\Porra;
use App\Models\Invitacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Partido;

/**
 * Controlador ParticipacionController
 *
 * Se encarga de la acción de “unirse a una porra”.
 *
 * Pantallas / flujo relacionado:
 * - Desde la pantalla principal de una porra (8.7) el usuario puede unirse.
 * - Tras unirse, la porra debe aparecer en “Mis porras” (8.6).
 *
 * Reglas principales:
 * - El usuario debe estar autenticado.
 * - La porra debe estar activa.
 * - No se permite que un usuario se apunte dos veces a la misma porra.
 * - Si hay límite de participantes, se respeta.
 * - Si la porra es privada, se requiere invitación pendiente.
 * - Si se usa invitación, se marca como aceptada. 
 */
class ParticipacionController extends Controller
{
    /**
     * Unirse a una porra (CU5).
     *
     * @param Request $request
     * @param int $id  Id de la porra
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unirse(Request $request, int $id)
    {
        // Autenticación manual por sesión
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $userId = $usuario->id_usuario;
        $porra  = Porra::findOrFail($id);

        // 1) La porra debe estar activa
        if ($porra->estado !== 'activa') {
            return back()->withErrors([
                'general' => 'La porra no está activa o no admite nuevas inscripciones.',
            ]);
        }


        // 2) La competición no debe haber comenzado 
        if (Partido::competicionHaComenzado($porra->id_competicion)) {
            return back()->withErrors([
                'general' => 'La competición ya ha comenzado. No es posible unirse a esta porra.'
            ]);
        }

        // 3) Evitar duplicados: si ya participa, no permitir
        $yaParticipa = Participacion::where('id_usuario', $userId)
            ->where('id_porra', $porra->id_porra)
            ->exists();

        if ($yaParticipa) {
            return back()->withErrors([
                'general' => 'Ya participas en esta porra.',
            ]);
        }

        // 4) Respetar máximo de participantes si existe
        if (!is_null($porra->max_participantes)) {
            $numParticipantes = Participacion::where('id_porra', $porra->id_porra)->count();
            if ($numParticipantes >= $porra->max_participantes) {
                return back()->withErrors([
                    'general' => 'La porra ha alcanzado el máximo de participantes.',
                ]);
            }
        }

        // 5) Si la porra es privada, exigir invitación pendiente
        $invitacion = null;

        if ((int) $porra->es_publica === 0) {
            $invitacion = Invitacion::where('id_porra', $porra->id_porra)
                ->where('estado', 'pendiente')
                ->where(function ($q) use ($usuario) {
                    $q->where('usuario_destino', $usuario->nombre_usuario)
                        ->orWhere('email_destino', $usuario->correo_electronico);
                })
                ->orderByDesc('fecha_envio')
                ->first();

            if (!$invitacion) {
                return back()->withErrors([
                    'general' => 'Esta porra es privada. Necesitas una invitación pendiente para unirte.',
                ]);
            }
        }

        // 6) Guardar la participación y actualizar invitación (si aplica) en una transacción
        DB::transaction(function () use ($userId, $porra, $invitacion) {

            Participacion::create([
                'id_usuario' => $userId,
                'id_porra'   => $porra->id_porra,
                'es_admin'   => 0,
                'puntos'     => 0,
                'posicion'   => null,
            ]);

            if ($invitacion) {
                $invitacion->estado = 'aceptada';
                $invitacion->fecha_respuesta = now();
                $invitacion->save();
            }
        });

        // 7) Tras unirse, redirigimos a “Mis porras” (8.6)
        return redirect()
            ->route('porras.mis')
            ->with('success', 'Te has unido a la porra correctamente.');
    }
}
