<?php

namespace App\Http\Controllers;

use App\Models\Partido;
use App\Models\Porra;
use App\Models\Pronostico;
use App\Models\Participacion;
use Illuminate\Http\Request;

/**
 * Controlador PronosticoController
 *
 * Pantalla 8.8 – Enviar / modificar pronósticos de partidos.
 *
 * Idea general:
 * - El usuario debe estar autenticado y participar en la porra.
 * - Solo se permite enviar/actualizar pronósticos mientras el partido esté programado.
 * - Los pronósticos se guardan con updateOrCreate para no duplicar registros.
 */
class PronosticoController extends Controller
{
    /**
     * Muestra la pantalla 8.8 con los partidos futuros de la competición asociada a la porra.
     * También carga los pronósticos ya existentes del usuario para rellenar el formulario.
     */
    public function index(int $idPorra)
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $userId = $usuario->id_usuario;
        $porra  = Porra::findOrFail($idPorra);

        // El usuario debe estar inscrito en la porra para poder pronosticar
        $inscrito = Participacion::where('id_usuario', $userId)
            ->where('id_porra', $porra->id_porra)
            ->exists();

        abort_unless($inscrito, 403, 'Debes unirte a la porra para enviar pronósticos.');

        // Solo mostramos partidos que aún no han empezado
        $partidos = Partido::where('id_competicion', $porra->id_competicion)
            ->where('fecha_hora', '>', now())
            ->orderBy('fecha_hora')
            ->get();

        // Pronósticos ya guardados por el usuario (para precargar el formulario)
        $pronosticos = Pronostico::where('id_usuario', $userId)
            ->where('id_porra', $porra->id_porra)
            ->get()
            ->keyBy('id_partido');

        return view('pronosticos.index', compact('porra', 'partidos', 'pronosticos'));
    }

    /**
     * Guarda pronósticos enviados en bloque (uno por partido).
     *
     * Reglas:
     * - Si el usuario deja un partido vacío, no se guarda nada para ese partido.
     * - Si rellena un partido, debe rellenar local y visitante.
     * - Solo se guarda si el partido sigue "programado".
     */
    public function store(Request $request, int $idPorra)
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $userId = $usuario->id_usuario;
        $porra  = Porra::findOrFail($idPorra);

        // El usuario debe estar inscrito en la porra
        $inscrito = Participacion::where('id_usuario', $userId)
            ->where('id_porra', $porra->id_porra)
            ->exists();

        abort_unless($inscrito, 403, 'Debes unirte a la porra para enviar pronósticos.');

        // Validación de estructura: pronosticos[IDPARTIDO][local|visitante]
        $request->validate([
            'pronosticos' => 'required|array',
            'pronosticos.*.local' => 'nullable|integer|min:0|max:99',
            'pronosticos.*.visitante' => 'nullable|integer|min:0|max:99',
        ]);

        foreach ($request->input('pronosticos', []) as $idPartido => $goles) {

            $local     = $goles['local'] ?? null;
            $visitante = $goles['visitante'] ?? null;

            // Si ambos están vacíos, el usuario no ha querido pronosticar ese partido
            if ($local === null && $visitante === null) {
                continue;
            }

            // Si ha empezado a rellenar, debe rellenar los dos
            if ($local === null || $visitante === null) {
                return back()->withErrors([
                    'general' => 'Si introduces un pronóstico, debes rellenar goles local y visitante.',
                ])->withInput();
            }

            // Validar que el partido pertenece a la misma competición de la porra
            $partido = Partido::where('id_partido', $idPartido)
                ->where('id_competicion', $porra->id_competicion)
                ->first();

            if (!$partido) {
                return back()->withErrors([
                    'general' => 'Se ha detectado un partido inválido en el envío.',
                ]);
            }

            // No permitir cambios si el partido ya no está programado
            if ($partido->estado !== 'programado') {
                continue;
            }

            // Guardar o actualizar (evita duplicados por usuario/porra/partido)
            Pronostico::updateOrCreate(
                [
                    'id_usuario' => $userId,
                    'id_porra'   => $porra->id_porra,
                    'id_partido' => $partido->id_partido,
                ],
                [
                    'goles_local_pronosticados'     => $local,
                    'goles_visitante_pronosticados' => $visitante,
                    'puntos_obtenidos'              => 0,
                ]
            );
        }

        // Tras guardar, volvemos a la pantalla principal de la porra (8.7)
        return redirect()
            ->route('porras.show', $porra->id_porra)
            ->with('success', 'Pronósticos guardados correctamente.');
    }
}