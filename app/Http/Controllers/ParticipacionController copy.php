<?php

namespace App\Http\Controllers;

use App\Models\Participacion;
use App\Models\Porra;
use App\Models\Invitacion; // OJO: tu modelo se llama así ahora mismo
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ParticipacionController extends Controller
{
    /**
     * CU5 - Unirse a una porra.
     *
     * Reglas (alineadas con tu BD y ERS):
     * - Usuario debe estar autenticado. (middleware auth)
     * - La porra debe estar 'activa'. (porras.estado) 
     * - Si la porra es privada, debe existir invitación 'pendiente' (por usuario o email). 
     * - No permitir duplicados (participaciones UNIQUE). 
     * - Respetar max_participantes si existe.
     * - Si se usa invitación, marcarla como 'aceptada' y guardar fecha_respuesta. 
     */
    public function unirse(Request $request, int $id)
    {
        $usuario = session('usuario'); // OJO: tu sesión guarda el usuario así, no uses Auth::user() aquí
        $userId = $usuario->id_usuario; // PK real de usuarios según tu script 

        $porra = Porra::findOrFail($id);

        // 1) Porra debe estar activa
        if ($porra->estado !== 'activa') {
            return back()->withErrors([
                'general' => 'La porra no está activa o no admite nuevas inscripciones.',
            ]);
        }

        // 2) Si ya participa, no permitir
        $yaParticipa = Participacion::where('id_usuario', $userId)
            ->where('id_porra', $porra->id_porra)
            ->exists();

        if ($yaParticipa) {
            return back()->withErrors([
                'general' => 'Ya participas en esta porra.',
            ]);
        }

        // 3) Respetar max_participantes si no es NULL
        if (!is_null($porra->max_participantes)) {
            $numParticipantes = Participacion::where('id_porra', $porra->id_porra)->count();
            if ($numParticipantes >= $porra->max_participantes) {
                return back()->withErrors([
                    'general' => 'La porra ha alcanzado el máximo de participantes.',
                ]);
            }
        }

        // 4) Si es privada: exigir invitación pendiente válida
        $invitacion = null;
        if ((int)$porra->es_publica === 0) {
            $invitacion = Invitacion::where('id_porra', $porra->id_porra)
                ->where('estado', 'pendiente')
                ->where(function ($q) use ($usuario) {
                    $q->where('usuario_destino', $usuario->nombre_usuario)
                      ->orWhere('email_destino', $usuario->correo_electronico);
                })
                ->orderByDesc('fecha_envio') // por si hubiese varias antiguas (la más reciente)
                ->first();

            if (!$invitacion) {
                return back()->withErrors([
                    'general' => 'Esta porra es privada. Necesitas una invitación pendiente para unirte.',
                ]);
            }
        }

        // 5) Guardar participación + actualizar invitación (si aplica)
        DB::transaction(function () use ($userId, $porra, $invitacion) {

            Participacion::create([
                'id_usuario' => $userId,
                'id_porra' => $porra->id_porra,
                'es_admin' => 0,
                'puntos' => 0,
                'posicion' => null,
            ]);

            if ($invitacion) {
                $invitacion->estado = 'aceptada';
                $invitacion->fecha_respuesta = now();
                $invitacion->save();
            }
        });

        // 6) Postcondición CU5: la porra aparece en "Mis porras" (8.6) 
        return redirect()
            ->route('porras.mis')
            ->with('success', 'Te has unido a la porra correctamente.');
    }
}