<?php

namespace App\Console\Commands;

use App\Services\ClasificacionPorraService;
use Illuminate\Console\Command;

/**
 * Ejecuta el recálculo total de una porra.
 * Uso:
 *   php artisan porras:recalcular-clasificacion 10
 */
class RecalcularClasificacionPorra extends Command
{
    protected $signature = 'porras:recalcular-clasificacion {idPorra}';
    protected $description = 'Recalcula desde cero la clasificación de una porra (partidos + campeones)';

    public function handle(ClasificacionPorraService $service)
    {
        $idPorra = (int)$this->argument('idPorra');
        $service->recalcular($idPorra);

        $this->info('Clasificación recalculada correctamente.');
        return Command::SUCCESS;
    }
}