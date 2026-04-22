<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Definición de tareas programadas.
     *
     * No se utilizan tareas automáticas en este proyecto.
     */
    protected function schedule(Schedule $schedule)
    {
        // Sin tareas programadas
    }

    /**
     * Registro automático de comandos de consola.
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
    }
}