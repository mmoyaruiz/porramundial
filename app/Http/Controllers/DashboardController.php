<?php

namespace App\Http\Controllers;

use App\Models\Participacion;
use App\Models\Porra;

class DashboardController extends Controller
{
    /**
     * Panel de control del usuario
     *
     * Objetivo:
     * - Mostrar un resumen rápido del usuario: porras en las que participa,
     *   porras que administra y número de partidos pendientes de pronóstico.
     *
     */
    public function index()
    {
        // Usuario autenticado por sesión
        $usuario = session('usuario');

        // Si no hay sesión, se redirige al login
        if (!$usuario) {
            return redirect()->route('login');
        }

        /*
         * Estas consultas están encapsuladas en métodos del modelo Usuario.
         * Se usan para mantener el controlador limpio y reutilizar lógica en otras pantallas.
         */
        $porrasParticipa = $usuario->porrasParticipa();
        $porrasAdmin     = $usuario->porrasAdministra();

        // Se envía un resumen a la vista (limitamos listado para que el dashboard sea rápido y claro)
        return view('dashboard.index', [
            'usuario'       => $usuario,
            'porras'        => $porrasParticipa->limit(5)->get(),
            'numParticipa'  => $porrasParticipa->count(),
            'numAdmin'      => $porrasAdmin->count(),
            'numPendientes' => $usuario->partidosPendientesPronostico(),
        ]);
    }
}