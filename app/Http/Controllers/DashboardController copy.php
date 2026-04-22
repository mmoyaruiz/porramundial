<?php

namespace App\Http\Controllers;

use App\Models\Participacion;
use App\Models\Porra;
use Illuminate\Support\Facades\Auth;
use App\Models\Usuario;

class DashboardController extends Controller
{
    /**
     * Pantalla 8.5 – Panel de control del usuario
     */

    public function index()
    {
        $usuario = session('usuario');

        if (!$usuario) {
            return redirect()->route('login');
        }

        $porrasParticipa = $usuario->porrasParticipa();
        $porrasAdmin = $usuario->porrasAdministra();

        return view('dashboard.index', [
            'usuario'       => $usuario,
            'porras'        => $porrasParticipa->limit(5)->get(),
            'numParticipa'  => $porrasParticipa->count(),
            'numAdmin'      => $porrasAdmin->count(),
            'numPendientes' => $usuario->partidosPendientesPronostico(),
        ]);
    }
}
