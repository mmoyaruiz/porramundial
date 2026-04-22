<?php

namespace App\Console\Commands;

use App\Models\Partido;
use App\Services\CalculadorPuntosService;
use Illuminate\Console\Command;

class CalcularPuntosPartidos extends Command
{
    protected $signature = 'porras:calcular-puntos';
    protected $description = 'Calcula puntos de pronósticos para partidos finalizados';

    public function handle(CalculadorPuntosService $service)
    {
        $partidos = Partido::where('estado', 'finalizado')
            ->whereNotNull('goles_local')
            ->whereNotNull('goles_visitante')
            ->get();

        foreach ($partidos as $partido) {
            $service->calcularPorPartido($partido);
        }

        $this->info('Puntos calculados correctamente.');
    }
}