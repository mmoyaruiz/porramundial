<?php

namespace App\Console\Commands;

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
     * Uso:
     *   php artisan porras:recalcular-clasificacion {idPorra}
     */
    protected $signature = 'porras:recalcular-clasificacion {idPorra}';

    protected $description = 'Recalcula desde cero la clasificación de una porra';

    /**
     * Ejecuta el recálculo completo de la clasificación.
     *
     * @param ClasificacionPorraService $service
     * @return int
     */
    public function handle(ClasificacionPorraService $service)
    {
        $idPorra = (int) $this->argument('idPorra');

        // Delegamos toda la lógica en el servicio
        $service->recalcular($idPorra);

        $this->info('Clasificación recalculada correctamente.');
        return Command::SUCCESS;
    }
}