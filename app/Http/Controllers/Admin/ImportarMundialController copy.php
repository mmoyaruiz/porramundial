<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ImportarMundialController extends Controller
{
    /**
     * Ejecuta el comando Artisan pm:importar-mundial
     * exactamente igual que en consola.
     */
    public function importar(int $porra)
    {



        set_time_limit(0); // Evitar timeout en importaciones largas
        ignore_user_abort(true); // Continuar incluso si se cierra la conexión



        // Verificar que el usuario está autenticado
        if (!session()->has('id_usuario')) {
            return redirect('/login');
        }


        // Log solo para comprobar que entra aquí

        Log::info('Importación Mundial lanzada desde web', [
            'porra_id' => $porra,
        ]);

        // MISMA EJECUCIÓN QUE EN CONSOLA

        $exitCode = Artisan::call('pm:importar-mundial', [], null);




        Log::info('Resultado importar mundial', [
            'exit_code' => $exitCode,
            'output' => Artisan::output(),
        ]);


        // Volvemos SIEMPRE a la página principal de la porra
        return redirect()
            ->route('porra.show', $porra)
            ->with(
                'status',
                $exitCode === 0
                    ? 'Partidos del Mundial actualizados correctamente'
                    : 'Error al actualizar los partidos del Mundial'
            );
    }
}
