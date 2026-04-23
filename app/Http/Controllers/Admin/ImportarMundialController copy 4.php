<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

// AJUSTA el namespace/modelo si tu modelo Porra está en otro sitio
use App\Models\Porra;

class ImportarMundialController extends Controller
{
    public function importar(int $porra)
    {
        /**
         * 1) Seguridad según TU sistema (sesión propia)
         * Ajusta la clave si en tu sesión guardas el usuario de otra forma.
         */
        $usuario = session('usuario'); // suele ser array/objeto en proyectos personalizados
        $idUsuario = $usuario['id_usuario'] ?? session('id_usuario'); // fallback por si guardas solo el id

        if (!$idUsuario) {
            return redirect('/login');
        }

        /**
         * 2) Cargar porra y comprobar admin
         * Ajusta el campo de "creador/admin" según tu BD:
         * - Puede ser id_usuario_creador, id_usuario_admin, id_usuario...
         */
        $porraModel = Porra::where('id_porra', $porra)->firstOrFail();

        // 🔧 AJUSTA ESTE CAMPO:
        $idAdminPorra = $porraModel->id_usuario_creador ?? $porraModel->id_usuario_admin ?? $porraModel->id_usuario;

        if ((int)$idUsuario !== (int)$idAdminPorra) {
            // Seguridad real: aunque alguien “fabrique” el POST, aquí se bloquea.
            abort(403, 'No tienes permisos para actualizar esta porra.');
        }

        /**
         * 3) Ejecutar importación
         */
        Log::info('Importación Mundial desde web', [
            'porra_id' => $porra,
            'usuario_id' => $idUsuario,
        ]);

        $exitImport = Artisan::call('pm:importar-mundial');
        $outputImport = trim(Artisan::output());

        /**
         * 4) Recalcular clasificación tras importación
         *
         * Como no puedo asumir el nombre exacto de tu comando/service,
         * te dejo dos alternativas:
         *  A) Si tienes comando Artisan de recálculo (lo más típico):
         *     porras:recalcular-clasificacion {idPorra}
         *  B) Si tienes un Service en PHP (mejor arquitectura):
         *     app(ClasificacionPorraService::class)->recalcular($porra)
         *
         * Usa una sola y deja la otra comentada.
         */

        $exitRecalc = null;
        $outputRecalc = null;

        // ---- A) RECÁLCULO vía comando Artisan (ajusta nombre si el tuyo es distinto)
        // OJO: Ajusta el nombre del comando a tu proyecto real.
        if (array_key_exists('porras:recalcular-clasificacion', Artisan::all())) {
            $exitRecalc = Artisan::call('porras:recalcular-clasificacion', [
                'idPorra' => $porraModel->id_porra, // o el nombre de argumento que tengas
            ]);
            $outputRecalc = trim(Artisan::output());
        } else {
            // ---- B) RECÁLCULO vía servicio (ajusta si lo tienes)
            // $exitRecalc = 0;
            // app(\App\Services\ClasificacionPorraService::class)->recalcular($porraModel->id_porra);
            // $outputRecalc = 'Clasificación recalculada (service)';
        }

        Log::info('Resultado importación/recalculo', [
            'exit_import' => $exitImport,
            'output_import' => $outputImport,
            'exit_recalc' => $exitRecalc,
            'output_recalc' => $outputRecalc,
        ]);

        /**
         * 5) Feedback visual (A)
         * Dejamos mensajes flash para mostrar en la vista.
         */
        $okImport = ($exitImport === 0);
        $okRecalc = ($exitRecalc === null || $exitRecalc === 0); // si no existe recálculo, no lo marcamos como fallo

        return redirect()->back()->with([
            'status_ok' => $okImport && $okRecalc,
            'status_msg' => $okImport
                ? 'Importación completada.'
                : 'Error en la importación.',
            'import_output' => $outputImport,
            'recalc_output' => $outputRecalc,
        ]);
    }
}