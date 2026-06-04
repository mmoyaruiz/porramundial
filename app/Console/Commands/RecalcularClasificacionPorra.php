<?php

namespace App\Console\Commands;

use App\Models\Porra;


use App\Services\ClasificacionPorraService;
use Illuminate\Console\Command;

/**
 * Comando RecalcularClasificacionPorra
 *
 * Este comando recalcula desde cero la clasificación de una porra concreta.
 *
 * Incluye:
 * - Puntos obtenidos por pronósticos de partidos.
 * - Puntos obtenidos por pronósticos de campeones.
 *
 * Se utiliza principalmente para:
 * - Pruebas.
 * - Corrección de errores.
 * - Recalcular la clasificación tras cambios en resultados o lógica de puntos.
 */
class RecalcularClasificacionPorra extends Command
{
    /**
     * Firma del comando.
     *
     * Uso: php artisan porras:recalcular-clasificacion {idPorra}
     */
    protected $signature = 'porras:recalcular-clasificacion {idPorra?}';

    protected $description = 'Recalcula desde cero la clasificación de una o varias porras';

    /**
     * Ejecuta el recálculo completo de la clasificación.
     *
     * @param ClasificacionPorraService $service
     * @return int
     */
    public function handle(ClasificacionPorraService $service)
    {


        $idPorra = (int) $this->argument('idPorra');

        /**
         * ✅ CASO 1: se pasa id → recalcular solo esa porra
         * (comportamiento equivalente al botón manual)
         */

        if ($idPorra) {
            // Delegamos toda la lógica en el servicio
            $service->recalcular($idPorra);

            $this->info('Clasificación recalculada correctamente.');
            return Command::SUCCESS;
        }

        /**
         * ✅ CASO 2: NO se pasa id → barrido por todas las porras activas
         * (comportamiento automático vía scheduler)
         */
        $porras = Porra::where('estado', 'activa')->get();

        if ($porras->isEmpty()) {
            $this->warn('No hay porras activas para recalcular.');
            return Command::SUCCESS;
        }

        $this->info('Recalculando clasificación de ' . $porras->count() . ' porras activas...');

        foreach ($porras as $porra) {
            try {
                $service->recalcular($porra->id_porra);
                $this->line("✔ Porra '{$porra->nombre}' recalculada");
            } catch (\Throwable $e) {
                // Muy importante: no rompemos todo el proceso
                $this->error("✖ Error en porra ID {$porra->id}: {$e->getMessage()}");
            }
        }

        $this->info('Proceso de recálculo finalizado.');
        return Command::SUCCESS;
    }
}
