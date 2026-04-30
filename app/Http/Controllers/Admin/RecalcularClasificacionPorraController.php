<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\Porra;

class RecalcularClasificacionPorraController extends Controller
{
    /**
     * BOTÓN 2:
     * Recalcula la clasificación de la porra (desde cero) usando:
     *   php artisan porras:recalcular-clasificacion
     */
    public function recalcular(int $porra)
    {
        // 1) Seguridad según TU sistema (sesión propia)
        $usuario = session('usuario');
        $idUsuario = $usuario['id_usuario'] ?? session('id_usuario');

        if (!$idUsuario) {
            return redirect('/login');
        }

        // 2) Cargar porra y comprobar admin (seguridad real)
        $porraModel = Porra::where('id_porra', $porra)->firstOrFail();

        $idAdminPorra = $porraModel->id_usuario_creador
            ?? $porraModel->id_usuario_admin
            ?? $porraModel->id_usuario;

        if ((int)$idUsuario !== (int)$idAdminPorra) {
            abort(403, 'No tienes permisos para recalcular la clasificación de esta porra.');
        }

        // 3) Ejecutar comando de recálculo 

        [$exitRecalc, $outputRecalc] = $this->callRecalcularClasificacion($porraModel->id_porra);

        Log::info('BOTÓN 2 -> Recalcular clasificación', [
            'porra_id' => $porraModel->id_porra,
            'usuario_id' => $idUsuario,
            'exit_code' => $exitRecalc,
            'output' => $outputRecalc,
        ]);

        $ok = ($exitRecalc === 0);

        return redirect()->back()->with([
            'status_ok' => $ok,
            'status_msg' => $ok
                ? 'Clasificación recalculada correctamente.'
                : 'Error al recalcular la clasificación.',
            'recalc_output' => $outputRecalc,
        ]);
    }

    /**
     * Ejecuta porras:recalcular-clasificacion y devuelve el código de salida y la salida del comando.
     */
    private function callRecalcularClasificacion(int $idPorra): array
    {
        // intento 3: {idPorra}
        $exit = Artisan::call('porras:recalcular-clasificacion', ['idPorra' => $idPorra]);
        $out  = trim(Artisan::output());

        return [$exit, $out];
    }
}
