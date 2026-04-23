<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ImportarMundialController extends Controller
{
    public function importar(int $porra)
    {
        // Seguridad según TU sistema de sesión
        if (!session()->has('usuario')) {
            return redirect('/login');
        }

        // Ejecutar el comando Artisan
        $exitCode = Artisan::call('pm:importar-mundial');
        $output = Artisan::output();

        // Log (opcional pero muy recomendable para evidencias)
        Log::info('Importación Mundial ejecutada desde web', [
            'porra_id' => $porra,
            'exit_code' => $exitCode,
            'output' => $output,
        ]);

        // Redirigir de nuevo a la porra con mensaje
        return redirect()
            ->route('porras.show', ['id' => $porra])
            ->with(
                'status',
                $exitCode === 0
                    ? 'Partidos del Mundial actualizados correctamente'
                    : 'Error al actualizar los partidos del Mundial'
            );
    }
}
