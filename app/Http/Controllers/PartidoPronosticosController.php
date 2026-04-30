<?php

namespace App\Http\Controllers;

use App\Models\Porra;
use App\Models\Partido;
use App\Models\Pronostico;

/**
 * Controlador PartidoPronosticosController
 *
 * Detalle de un partido dentro de una porra.
 *
 * Comportamiento principal:
 * - Si el partido está PROGRAMADO: solo se muestra el pronóstico del usuario logueado.
 * - Si el partido está EN JUEGO o FINALIZADO: se muestran los pronósticos de todos los participantes.
 *
 * Objetivo: evitar que un usuario vea pronósticos ajenos antes de que empiece el partido.
 */
class PartidoPronosticosController extends Controller
{
    /**
     * Muestra el detalle de partido  para una porra concreta.
     *
     * @param int $idPorra
     * @param int $idPartido
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show($idPorra, $idPartido)
    {
        // Autenticación manual por sesión
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $porra   = Porra::findOrFail($idPorra);
        $partido = Partido::findOrFail($idPartido);

        // Se considera que el partido ha comenzado cuando deja de estar "programado"
        $haComenzado = ($partido->estado !== 'programado');

        /*
         * Pronósticos:
         * - Si NO ha comenzado: solo el pronóstico del usuario logueado (privacidad).
         * - Si ha comenzado: pronósticos de todos, ordenados por puntos en la porra.
         */
        if ($haComenzado) {

            $pronosticos = Pronostico::where('pronosticos.id_porra', $idPorra)
                ->where('pronosticos.id_partido', $idPartido)
                ->join('usuarios', 'usuarios.id_usuario', '=', 'pronosticos.id_usuario')
                ->join('participaciones', function ($join) use ($idPorra) {
                    $join->on('participaciones.id_usuario', '=', 'usuarios.id_usuario')
                        ->where('participaciones.id_porra', '=', $idPorra);
                })
                ->select(
                    'usuarios.id_usuario',
                    'usuarios.nombre_usuario',
                    'pronosticos.goles_local_pronosticados',
                    'pronosticos.goles_visitante_pronosticados',
                    'participaciones.puntos'
                )
                ->orderByDesc('participaciones.puntos')
                ->get();

        } else {

            $pronosticos = Pronostico::where('pronosticos.id_porra', $idPorra)
                ->where('pronosticos.id_partido', $idPartido)
                ->where('pronosticos.id_usuario', $usuario->id_usuario)
                ->join('usuarios', 'usuarios.id_usuario', '=', 'pronosticos.id_usuario')
                ->select(
                    'usuarios.id_usuario',
                    'usuarios.nombre_usuario',
                    'pronosticos.goles_local_pronosticados',
                    'pronosticos.goles_visitante_pronosticados'
                )
                ->get();
        }

        /*
         * Se muestra primero al usuario logueado en el listado (si aparece).
         * No cambia datos, solo el orden visual.
         */
        $pronosticos = $pronosticos->sortByDesc(
            fn ($p) => $p->id_usuario === $usuario->id_usuario
        );

        /*
         * Marcador real:
         * - Se usa para resaltar aciertos en la vista cuando haya goles disponibles.
         * - Si aún no hay marcador (null), se deja como null para evitar falsos aciertos.
         */
        $marcadorReal = null;

        if ($partido->goles_local !== null && $partido->goles_visitante !== null) {
            $marcadorReal = [
                'local'     => $partido->goles_local,
                'visitante' => $partido->goles_visitante,
            ];
        }

        return view('porras.partido', compact(
            'porra',
            'partido',
            'pronosticos',
            'marcadorReal',
            'haComenzado'
        ));
    }
}