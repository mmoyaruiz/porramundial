<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tareas programadas (Scheduler)
|--------------------------------------------------------------------------
| Aquí declaramos los comandos que queremos ejecutar automáticamente.
| Después, "php artisan schedule:run" evaluará cada minuto qué toca ejecutar.
*/



// 1️⃣ Importar / actualizar partidos del Mundial
Schedule::command('pm:importar-mundial')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();


Schedule::command('pm:importar-campeones')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();


//Schedule::command('porra:actualizar-partidos')->everyMinute();
Schedule::command('porras:recalcular-clasificacion')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();





