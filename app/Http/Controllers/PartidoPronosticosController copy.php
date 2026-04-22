<?php

namespace App\Http\Controllers;

use App\Models\Porra;
use App\Models\Partido;
use App\Models\Pronostico;
use App\Models\Participacion;
use Carbon\Carbon;

class PartidoPronosticosController extends Controller
{
    public function show($idPorra, $idPartido)
    {
        // Usuario autenticado por sesión
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        $porra = Porra::findOrFail($idPorra);
        $partido = Partido::findOrFail($idPartido);



        // ¿El partido ha comenzado?
        $haComenzado = $partido->estado !== 'programado';

        if ($haComenzado) {

            // ✅ Partido en juego o finalizado → todos los pronósticos
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

            // 🔒 Partido programado → SOLO pronóstico del usuario logueado
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



        // Usuario logueado primero
        $usuario = session('usuario');
        $pronosticos = $pronosticos->sortByDesc(
            fn($p) => $p->id_usuario === $usuario->id_usuario
        );



        // Marcador real (solo si el partido ya empezó)
        $marcadorReal = null;
        if ($partido->status !== 'SCHEDULED') {
            $marcadorReal = [
                'local' => $partido->goles_local,
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
