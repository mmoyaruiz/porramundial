<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;






class Kernel extends ConsoleKernel
{



   
    protected function schedule(Schedule $schedule)
    {
        // 1️⃣ Actualización de partidos cada minuto
        //$schedule->command('porra:actualizar-partidos')
        //    ->everyMinute()
        //    ->withoutOverlapping()
        //    ->onOneServer();

        // 2️⃣ Recalcular clasificación cada minuto
        //$schedule->command('porras:recalcular-clasificacion')
        //    ->everyMinute()
        //    ->withoutOverlapping()
        //    ->onOneServer();

        //$schedule->command('porras:recalcular-clasificacion')
        //    ->everyMinute();
    }
       

    /**
     * Registro automático de comandos de consola.
     */
    protected function commands()
    {

        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
