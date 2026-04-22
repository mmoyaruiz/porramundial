<?php

namespace App\Http\Controllers;

use App\Models\Porra;
use App\Models\Pronostico;
use App\Models\Participacion;
use App\Models\Usuario;
use App\Models\Partido;


class PronosticoConsultaController extends Controller
{
    /**
     * CU8 – Pantalla 8.10
     * Ver MIS pronósticos en una porra
     */
    public function misPronosticos($idPorra)
{
    // Usuario autenticado por sesión (tu sistema)
    $usuario = session('usuario');
    if (!$usuario) {
        return redirect()->route('login');
    }

    // Porra
    $porra = Porra::findOrFail($idPorra);

    // Partidos de la competición (TODOS)
    $partidos = Partido::where('id_competicion', $porra->id_competicion)
        ->orderBy('fecha_hora')
        ->get();

    // Mis pronósticos (SIN filtrar por estado)
    $pronosticos = Pronostico::where('id_porra', $idPorra)
        ->where('id_usuario', $usuario->id_usuario)
        ->get()
        ->keyBy('id_partido');


    $participante = \App\Models\Participacion::where('id_porra', $porra->id_porra)
    ->where('id_usuario', $usuario->id_usuario)
    ->select('posicion', 'puntos')
    ->first();


    return view('pronosticos.mis', compact(
        'porra',
        'partidos',
        'pronosticos',
        'participante'
    ));
}

    /**
     * CU8 – Pantalla 8.11
     * Ver pronósticos de OTRO participante
     */
    
public function pronosticosUsuario($idPorra, $idUsuario)
{
    $usuarioLogueado = session('usuario');
    if (!$usuarioLogueado) {
        return redirect()->route('login');
    }

    $porra = Porra::findOrFail($idPorra);

    // Participante consultado
    $participante = Participacion::where('participaciones.id_porra', $idPorra)
        ->where('participaciones.id_usuario', $idUsuario)
        ->join('usuarios', 'usuarios.id_usuario', '=', 'participaciones.id_usuario')
        ->select(
            'usuarios.id_usuario',
            'usuarios.nombre_usuario',
            'participaciones.puntos',
            'participaciones.posicion'
        )
        ->firstOrFail();

    // Partidos de la competición
    $partidos = Partido::where('id_competicion', $porra->id_competicion)
        ->orderBy('fecha_hora')
        ->get();

    // Pronósticos SOLO de partidos en juego o finalizados
    $pronosticos = Pronostico::where('id_porra', $idPorra)
        ->where('id_usuario', $idUsuario)
        ->whereIn('id_partido', function ($q) {
            $q->select('id_partido')
              ->from('partidos')
              ->whereIn('estado', ['en_juego', 'finalizado']);
        })
        ->get()
        ->keyBy('id_partido');

    return view('porras.pronosticos_participante', compact(
        'porra',
        'participante',
        'partidos',
        'pronosticos'
    ));
}

}
