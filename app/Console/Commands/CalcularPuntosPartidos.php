<?php

namespace App\Console\Commands;

use App\Models\Partido;
use App\Services\CalculadorPuntosService;
use Illuminate\Console\Command;

/**
 * Comando CalcularPuntosPartidos
 *
 * Este comando calcula los puntos obtenidos en los pronósticos de partidos
 * que ya han finalizado.
 *
 * Se ejecuta sobre todos los partidos con estado "finalizado" y con marcador real,
 * delegando el cálculo concreto de puntos al servicio CalculadorPuntosService.
 *
 * Este proceso está alineado con la lógica del sistema de puntuación definida
 * en el proyecto y permite actualizar los puntos de forma masiva.
 */
class CalcularPuntosPartidos extends Command
{
    /**
     * Nombre del comando y descripción.
     */
    protected $signature = 'porras:calcular-puntos';
    protected $description = 'Calcula los puntos de los pronósticos para partidos finalizados';

    /**
     * Ejecuta el cálculo de puntos.
     *
     * Recorre todos los partidos finalizados con marcador y calcula los puntos
     * correspondientes a los pronósticos de cada partido.
     */
    public function handle(CalculadorPuntosService $service)
    {
        // Seleccionamos solo partidos finalizados con goles registrados
        $partidos = Partido::where('estado', 'finalizado')
            ->whereNotNull('goles_local')
            ->whereNotNull('goles_visitante')
            ->get();

        // Calculamos los puntos partido a partido
        foreach ($partidos as $partido) {
            $service->calcularPorPartido($partido);
        }

        $this->info('Puntos calculados correctamente.');
    }
}