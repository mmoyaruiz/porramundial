<?php

namespace App\Http\Controllers;

use App\Models\Partido;
use App\Models\Participacion;
use App\Models\Porra;
use App\Models\Pronostico;

/**
 * Controlador TablaPartidosController
 *
 * Tabla con últimos partidos (en juego o finalizados) y pronósticos por participante.
 *
 * La idea de esta pantalla es mostrar una “matriz”:
 * - Columnas: hasta 10 partidos más recientes (en juego o finalizados).
 * - Filas: participantes de la porra.
 * - Celdas: marcador pronosticado por cada participante para cada partido.
 */
class TablaPartidosController extends Controller
{
    /**
     * Muestra la pantalla para una porra concreta.
     *
     * @param int $idPorra
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index($idPorra)
    {
        // Autenticación manual por sesión
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $porra = Porra::findOrFail($idPorra);

        /*
         * 1) Selección de partidos:
         * - Solo partidos en juego o finalizados (son los que tienen interés en “últimos partidos”).
         * - Máximo 10 para mantener la tabla manejable.
         * - Se invierte el orden para que el más antiguo quede a la izquierda.
         */
        $partidos = Partido::where('id_competicion', $porra->id_competicion)
            ->whereIn('estado', ['en_juego', 'finalizado'])
            ->orderByDesc('fecha_hora')
            ->limit(10)
            ->get()
            ->reverse();

        /*
         * 2) Participantes:
         * - Se listan ordenados por posición (clasificación actual).
         * - Se une con usuarios para mostrar el nombre visible.
         */
        $participantes = Participacion::where('id_porra', $idPorra)
            ->join('usuarios', 'usuarios.id_usuario', '=', 'participaciones.id_usuario')
            ->select('usuarios.id_usuario', 'usuarios.nombre_usuario')
            ->orderBy('participaciones.posicion')
            ->get();

        /*
         * 3) Pronósticos:
         * - Se cargan solo los pronósticos de los partidos que aparecen en la tabla.
         * - Se agrupan por usuario y dentro por partido para acceso rápido en la vista:
         *   $pronosticos[$idUsuario][$idPartido]
         */
        $pronosticos = Pronostico::where('id_porra', $idPorra)
            ->whereIn('id_partido', $partidos->pluck('id_partido'))
            ->get()
            ->groupBy('id_usuario')
            ->map(fn ($p) => $p->keyBy('id_partido'));

        return view('porras.ultimos_partidos', compact(
            'porra',
            'partidos',
            'participantes',
            'pronosticos'
        ));
    }
}