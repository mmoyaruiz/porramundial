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

        // 3) Ejecutar comando de recálculo (sin calcular-puntos, porque es redundante según tú)
        // OJO: como no puedo asegurar el nombre exacto del argumento en tu signature,
        // hago 3 intentos típicos: id, porra, idPorra.
        [$exitRecalc, $outputRecalc] = $this->callRecalcularClasificacion($porraModel->id_porra);

        Log::info('BOTÓN 2 -> Recalcular clasificación', [
            'porra_id' => $porraModel->id_porra,
            'usuario_id' => $idUsuario,
            'exit_code' => $exitRecalc,
            'output' => $outputRecalc,
        ]);

        // 4) Mensaje para UI (A)
        $ok = ($exitRecalc === 0);

        return redirect()->back()->with([
            'status_ok' => $ok,
            'status_msg' => $ok
                ? '✅ Clasificación recalculada correctamente.'
                : '❌ Error al recalcular la clasificación.',
            'recalc_output' => $outputRecalc,
        ]);
    }

    /**
     * Intenta ejecutar porras:recalcular-clasificacion con distintas claves
     * por si el signature usa {id} o {porra} o {idPorra}.
     */
    private function callRecalcularClasificacion(int $idPorra): array
    {
        // intento 1: {id}
        //$exit = Artisan::call('porras:recalcular-clasificacion', ['id' => $idPorra]);
        //$out  = trim(Artisan::output());
        //if ($exit === 0) return [$exit, $out];

        // intento 2: {porra}
        //$exit = Artisan::call('porras:recalcular-clasificacion', ['porra' => $idPorra]);
        //$out  = trim(Artisan::output());
        //if ($exit === 0) return [$exit, $out];

        // intento 3: {idPorra}
        $exit = Artisan::call('porras:recalcular-clasificacion', ['idPorra' => $idPorra]);
        $out  = trim(Artisan::output());

        return [$exit, $out];
    }
}
