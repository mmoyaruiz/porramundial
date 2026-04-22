<?php

namespace App\Http\Controllers;

use App\Models\Porra;
use App\Models\Pronostico;
use App\Models\Participacion;
use App\Models\Partido;

/**
 * Controlador PronosticoConsultaController
 *
 * Gestiona la consulta de pronósticos ya enviados.
 *
 * Pantallas asociadas (ERS):
 * - 8.10: Ver MIS pronósticos en una porra.
 * - 8.11: Ver los pronósticos de OTRO participante.
 *
 * Regla general:
 * - El usuario debe estar autenticado (sesión).
 * - Antes de que empiece un partido, no se muestran pronósticos ajenos.
 */
class PronosticoConsultaController extends Controller
{
    /**
     * 8.10 – Ver mis pronósticos en una porra
     *
     * Muestra todos los partidos de la competición y los pronósticos
     * enviados por el usuario logueado, independientemente del estado del partido.
     */
    public function misPronosticos($idPorra)
    {
        // Usuario autenticado por sesión
        $usuario = session('usuario');
        if (!$usuario) {
            return redirect()->route('login');
        }

        // Porra consultada
        $porra = Porra::findOrFail($idPorra);

        // Todos los partidos de la competición (ordenados por fecha)
        $partidos = Partido::where('id_competicion', $porra->id_competicion)
            ->orderBy('fecha_hora')
            ->get();

        // Pronósticos del usuario para esta porra (indexados por partido)
        $pronosticos = Pronostico::where('id_porra', $idPorra)
            ->where('id_usuario', $usuario->id_usuario)
            ->get()
            ->keyBy('id_partido');

        // Información del participante dentro de la porra (posición y puntos)
        $participante = Participacion::where('id_porra', $porra->id_porra)
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
     * 8.11 – Ver pronósticos de otro participante
     *
     * Permite consultar los pronósticos de otro usuario de la porra,
     * pero únicamente para partidos que ya estén en juego o finalizados.
     */
    public function pronosticosUsuario($idPorra, $idUsuario)
    {
        // Usuario autenticado por sesión
        $usuarioLogueado = session('usuario');
        if (!$usuarioLogueado) {
            return redirect()->route('login');
        }

        $porra = Porra::findOrFail($idPorra);

        // Datos del participante consultado dentro de la porra
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

        // Todos los partidos de la competición
        $partidos = Partido::where('id_competicion', $porra->id_competicion)
            ->orderBy('fecha_hora')
            ->get();

        // Pronósticos del participante SOLO para partidos ya iniciados o finalizados
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