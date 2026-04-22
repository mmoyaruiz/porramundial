<?php

namespace App\Http\Controllers;

use App\Models\Partido;
use App\Models\Porra;
use App\Models\Pronostico;
use App\Models\Participacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PronosticoController
 *
 * CU7 – Enviar pronósticos (Pantalla 8.8): listado de partidos + envío de pronósticos. 
 * Reglas:
 * - Usuario debe estar autenticado (middleware auth)
 * - Usuario debe participar en la porra (precondición CU7) 
 * - Solo se permite crear/editar si el partido NO ha empezado (fecha_hora > now)
 * - Respeta UNIQUE (id_usuario,id_porra,id_partido) mediante updateOrCreate 
 */
class PronosticoController extends Controller
{
    /**
     * GET /porras/{id}/pronosticos
     * Pantalla 8.8: mostrar partidos de la competición de la porra, ordenados cronológicamente. 
     */
    public function index(int $idPorra)
    {
        $usuario = session('usuario');
        $userId = $usuario->id_usuario;

        $porra = Porra::findOrFail($idPorra);

        // Precondición: el usuario debe estar inscrito en la porra
        $inscrito = Participacion::where('id_usuario', $userId)
            ->where('id_porra', $porra->id_porra)
            ->exists();

        abort_unless($inscrito, 403, 'Debes unirte a la porra para enviar pronósticos.');





        // Partidos de la competición asociada a la porra y que no hayan empezado aun (fecha_hora > ahora)
        $partidos = Partido::where('id_competicion', $porra->id_competicion)
            ->where('fecha_hora', '>', now())
            ->orderBy('fecha_hora')
            ->get();

        // Pronósticos existentes del usuario para esa porra (para rellenar formulario)
        $pronosticos = Pronostico::where('id_usuario', $userId)
            ->where('id_porra', $porra->id_porra)
            ->get()
            ->keyBy('id_partido');

        return view('pronosticos.index', compact('porra', 'partidos', 'pronosticos'));
    }

    /**
     * POST /porras/{id}/pronosticos
     * Guarda pronósticos enviados en bloque (uno por partido).
     */
    public function store(Request $request, int $idPorra)
    {
        $usuario = session('usuario');
        $userId = $usuario->id_usuario;

        $porra = Porra::findOrFail($idPorra);

        // Precondición: inscrito
        $inscrito = Participacion::where('id_usuario', $userId)
            ->where('id_porra', $porra->id_porra)
            ->exists();

        abort_unless($inscrito, 403, 'Debes unirte a la porra para enviar pronósticos.');

        // Validación: estructura de array pronosticos[IDPARTIDO][local|visitante]
        // NOTA: validamos que sean enteros >= 0
        $request->validate([
            'pronosticos' => 'required|array',
            'pronosticos.*.local' => 'nullable|integer|min:0|max:99',
            'pronosticos.*.visitante' => 'nullable|integer|min:0|max:99',
        ]);

        $now = now();

        foreach ($request->input('pronosticos', []) as $idPartido => $goles) {
            // Si el usuario dejó ambos campos vacíos, no guardamos nada
            $local = $goles['local'] ?? null;
            $visitante = $goles['visitante'] ?? null;

            if ($local === null && $visitante === null) {
                continue;
            }

            // Deben venir ambos si se quiere guardar
            if ($local === null || $visitante === null) {
                return back()->withErrors([
                    'general' => 'Si introduces un pronóstico, debes rellenar goles local y visitante.',
                ])->withInput();
            }

            // Comprobar que el partido pertenece a la competición de la porra
            $partido = Partido::where('id_partido', $idPartido)
                ->where('id_competicion', $porra->id_competicion)
                ->first();

            if (!$partido) {
                return back()->withErrors([
                    'general' => 'Se ha detectado un partido inválido en el envío.',
                ]);
            }

            // No permitir cambios si el partido ya ha empezado (fecha_hora <= ahora)
            if ($partido->estado !== 'programado') {
                // Lo ignoramos silenciosamente o puedes devolver error si prefieres.
                continue;
            }
            

            // Guardar / actualizar respetando UNIQUE (usuario, porra, partido) 
            Pronostico::updateOrCreate(
                [
                    'id_usuario' => $userId,
                    'id_porra' => $porra->id_porra,
                    'id_partido' => $partido->id_partido,
                ],
                [
                    'goles_local_pronosticados' => $local,
                    'goles_visitante_pronosticados' => $visitante,
                    'puntos_obtenidos' => 0,
                ]
            );
        }

        return redirect()
            ->route('porras.show', $porra->id_porra)
            ->with('success', 'Pronósticos guardados correctamente.');
    }
}
