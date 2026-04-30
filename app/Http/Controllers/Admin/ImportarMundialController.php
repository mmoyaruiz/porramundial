<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\Porra;

class ImportarMundialController extends Controller
{
    /**
     * BOTÓN 1:
     * - Descarga/actualiza partidos del Mundial desde API (pm:importar-mundial)
     * - Descarga/actualiza campeones reales (pm:importar-campeones)
     * NO recalcula clasificación aquí.
     */
    public function importar(int $porra)
    {
        // 1) Seguridad según TU sistema (sesión propia)
        $usuario = session('usuario'); // array/objeto en tu login propio
        $idUsuario = $usuario['id_usuario'] ?? session('id_usuario'); // fallback

        if (!$idUsuario) {
            return redirect('/login');
        }

        // 2) Cargar porra y comprobar admin
        $porraModel = Porra::where('id_porra', $porra)->firstOrFail();

        // Ajusta el campo real si lo tienes claro (creador/admin)
        $idAdminPorra = $porraModel->id_usuario_creador
            ?? $porraModel->id_usuario_admin
            ?? $porraModel->id_usuario;

        if ((int)$idUsuario !== (int)$idAdminPorra) {
            abort(403, 'No tienes permisos para actualizar esta porra.');
        }

        // 3) Ejecutar importación de PARTIDOS (marcadores)
        Log::info('BOTÓN 1 -> Importación Mundial (partidos)', [
            'porra_id' => $porra,
            'usuario_id' => $idUsuario,
        ]);

        $exitPartidos = Artisan::call('pm:importar-mundial');
        $outputPartidos = trim(Artisan::output()); // ej: "Procesados: 72 | ..."

        // 4) Ejecutar importación de CAMPEONES REALES
        Log::info('BOTÓN 1 -> Importación Campeones Reales', [
            'porra_id' => $porra,
            'usuario_id' => $idUsuario,
        ]);

        $exitCampeones = Artisan::call('pm:importar-campeones');
        $outputCampeones = trim(Artisan::output());

        // 5) Logs finales (útil para depurar y para evidencias de pruebas)
        Log::info('Resultado BOTÓN 1 (API)', [
            'exit_partidos' => $exitPartidos,
            'output_partidos' => $outputPartidos,
            'exit_campeones' => $exitCampeones,
            'output_campeones' => $outputCampeones,
        ]);

        // 6) Feedback visual
        $ok = ($exitPartidos === 0 && $exitCampeones === 0);

        // Mostramos un mensaje y detalles de la importación (número de partidos procesados, campeones actualizados, etc.)
        return redirect()->back()->with([
            'status_ok' => $ok,
            'status_msg' => $ok
                ? 'Datos actualizados desde la API (partidos + campeones de grupo).'
                : 'Error actualizando datos desde la API.',
            'import_output' => $outputPartidos,
            'champions_output' => $outputCampeones,
        ]);
    }
}
