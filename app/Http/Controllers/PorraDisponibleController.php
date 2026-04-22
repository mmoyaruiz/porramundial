<?php

namespace App\Http\Controllers;

use App\Models\Porra;
use Illuminate\Support\Facades\DB;

/**
 * Controlador PorraDisponibleController
 *
 * Pantalla: listado de “porras disponibles”.
 *
 * Objetivo:
 * - Mostrar porras públicas activas a las que el usuario NO pertenece.
 * - Mostrar porras privadas activas donde el usuario tiene una invitación pendiente.
 *
 * Nota: esta pantalla está pensada para usuarios autenticados (se obtiene el usuario desde sesión),
 * y se apoya en la tabla participaciones y en la tabla invitaciones.
 */
class PorraDisponibleController extends Controller
{
    /**
     * Muestra el listado de porras disponibles para el usuario logueado.
     *
     * Devuelve dos listados:
     * - $publicas: porras públicas activas donde el usuario aún no participa.
     * - $privadasInvitadas: porras privadas activas donde existe invitación pendiente para el usuario.
     */
    public function index()
    {
        $usuario = session('usuario');

        /*
         * 1) Porras públicas activas a las que NO pertenece el usuario
         * - Filtramos por es_publica=1 y estado='activa'
         * - Excluimos aquellas porras donde ya existe participación del usuario
         * - Excluimos aquellas porras cuya competición ya ha comenzado (algún partido en juego o finalizado)
         */
        $publicas = Porra::where('es_publica', 1)
            ->where('estado', 'activa')
            ->whereNotIn('id_porra', function ($q) use ($usuario) {
                $q->select('id_porra')
                    ->from('participaciones')
                    ->where('id_usuario', $usuario->id_usuario);
            })
            ->whereDoesntHave('competicion.partidos', function ($q) {
                $q->whereIn('estado', ['en_juego', 'finalizado']);
            })
            ->with('competicion')
            ->get();

        /*
         * 2) Porras privadas activas donde el usuario tiene invitación pendiente
         * - Filtramos por es_publica=0 y estado='activa'
         * - Incluimos solo porras cuyo id_porra aparece en invitaciones pendientes para el usuario
         *   (coincide por nombre de usuario o por email)
         */
        $privadasInvitadas = Porra::where('es_publica', 0)
            ->where('estado', 'activa')
            ->whereIn('id_porra', function ($q) use ($usuario) {
                $q->select('id_porra')
                    ->from('invitaciones')
                    ->where('estado', 'pendiente')
                    ->where(function ($q2) use ($usuario) {
                        $q2->where('usuario_destino', $usuario->nombre_usuario)
                            ->orWhere('email_destino', $usuario->correo_electronico);
                    });
            })
            ->with('competicion')
            ->get();

        return view('porras.disponibles', compact('publicas', 'privadasInvitadas'));
    }
}
