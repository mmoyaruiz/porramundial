<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Comandos personalizados de la aplicación.
     */
    protected $commands = [
        \App\Console\Commands\CalcularPuntosPartidos::class,
        \App\Console\Commands\ImportarPartidosMundial::class,
    ];

    /**
     * Definición de tareas programadas (no necesario para UD3).
     */
    protected function schedule(Schedule $schedule)
    {
        // Ejemplo futuro:
        // $schedule->command('porras:calcular-puntos')->hourly();
    }

    /**
     * Registro automático de comandos.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
    }




}