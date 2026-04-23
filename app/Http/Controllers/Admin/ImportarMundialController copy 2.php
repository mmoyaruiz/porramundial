<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ImportarMundialController extends Controller
{
    /**
     * Ejecuta el comando Artisan pm:importar-mundial
     * exactamente igual que en consola.
     */
    
public function importar(int $porra)
{
    // Ejecutamos el comando
    $exitCode = Artisan::call('pm:importar-mundial');
    $output = Artisan::output();

    // DEBUG REAL
    dd([
        'exit_code' => $exitCode,
        'output'    => $output,
    ]);
}

}
