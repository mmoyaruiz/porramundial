<?php

namespace App\Http\Controllers;

use App\Models\Porra;
use App\Models\Participacion;
use App\Models\Inivitacion;
use Illuminate\Support\Facades\Auth;

class PorraDisponibleController extends Controller
{
    public function index()
    {

        $usuario = session('usuario');

        // Porras públicas activas a las que NO pertenece
        $publicas = Porra::where('es_publica', 1)
            ->where('estado', 'activa')
            ->whereNotIn('id_porra', function ($q) use ($usuario) {
                $q->select('id_porra')
                  ->from('participaciones')
                  ->where('id_usuario', $usuario->id_usuario);
            })
            ->with('competicion')
            ->get();

        // Porras privadas con invitación pendiente
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