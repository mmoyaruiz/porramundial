<?php

namespace App\Http\Controllers;

use App\Models\Partido;
use App\Models\Participacion;
use App\Models\Porra;
use App\Models\Pronostico;

/**
 * TablaPartidosController
 *
 * Pantalla 8.12: Tabla con los últimos partidos y pronósticos por participante. 
 */
class TablaPartidosController extends Controller
{
    public function index($idPorra)
    {
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $porra = Porra::findOrFail($idPorra);

        // 1️⃣ Últimos 10 partidos cerrados o en juego
        $partidos = Partido::where('id_competicion', $porra->id_competicion)
            ->whereIn('estado', ['en_juego', 'finalizado'])
            ->orderByDesc('fecha_hora')
            ->limit(10)
            ->get()
            ->reverse(); // para que el más antiguo quede a la izquierda

        // 2️⃣ Participantes de la porra
        $participantes = Participacion::where('id_porra', $idPorra)
            ->join('usuarios', 'usuarios.id_usuario', '=', 'participaciones.id_usuario')
            ->select(
                'usuarios.id_usuario',
                'usuarios.nombre_usuario'
            )
            ->orderBy('participaciones.posicion')
            ->get();

        // 3️⃣ Pronósticos indexados [usuario][partido]
        $pronosticos = Pronostico::where('id_porra', $idPorra)
            ->whereIn('id_partido', $partidos->pluck('id_partido'))
            ->get()
            ->groupBy('id_usuario')
            ->map(fn($p) => $p->keyBy('id_partido'));

        return view('porras.ultimos_partidos', compact(
            'porra',
            'partidos',
            'participantes',
            'pronosticos'
        ));
    }
}
