<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CONTROLADORES
|--------------------------------------------------------------------------
| Se importan todos los controladores utilizados en las rutas web.
| Mantenerlos agrupados facilita el mantenimiento y la lectura.
*/
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PorraController;
use App\Http\Controllers\ParticipacionController;
use App\Http\Controllers\InvitacionController;
use App\Http\Controllers\PronosticoController;
use App\Http\Controllers\PronosticoConsultaController;
use App\Http\Controllers\TablaPartidosController;
use App\Http\Controllers\PorraDisponibleController;
use App\Http\Controllers\PartidoPronosticosController;
use App\Http\Controllers\CampeonesController;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
| Accesibles sin autenticación.
| Incluyen inicio, login, registro y ayuda en línea (UD3).
*/

Route::get('/', fn () => view('home'))->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

/*
| Ayuda en línea (requisito obligatorio UD3)
*/
Route::get('/ayuda', fn () => view('help.index'))->name('help.index');


/*
|--------------------------------------------------------------------------
| RUTAS PRIVADAS (USUARIO AUTENTICADO)
|--------------------------------------------------------------------------
| Middleware:
| - web
| - usuario.auth
|
| Todas las rutas dentro de este grupo requieren sesión iniciada.
*/

Route::middleware(['web', 'usuario.auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | AUTENTICACIÓN Y PANEL PRINCIPAL
    |--------------------------------------------------------------------------
    */
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | PORRAS (LISTADOS Y GESTIÓN)
    |--------------------------------------------------------------------------
    */
    Route::get('/mis-porras', [PorraController::class, 'misPorras'])
        ->name('porras.mis');

    Route::get('/porras-admin', [PorraController::class, 'administro'])
        ->name('porras.admin');

    Route::get('/porras/crear', [PorraController::class, 'create'])
        ->name('porras.create');

    Route::post('/porras', [PorraController::class, 'store'])
        ->name('porras.store');

    Route::get('/porras/{id}', [PorraController::class, 'show'])
        ->name('porras.show');

    Route::get('/porras-disponibles', [PorraDisponibleController::class, 'index'])
        ->name('porras.disponibles');

    Route::post('/porras/{id}/unirse', [ParticipacionController::class, 'unirse'])
        ->name('porras.unirse');


    /*
    |--------------------------------------------------------------------------
    | INVITACIONES A PORRAS (ADMIN)
    |--------------------------------------------------------------------------
    */
    Route::get('/porras/{id}/invitar', [InvitacionController::class, 'create'])
        ->name('porras.invitar');

    Route::post('/porras/{id}/invitar', [InvitacionController::class, 'store'])
        ->name('porras.invitar.store');


    /*
    |--------------------------------------------------------------------------
    | PRONÓSTICOS DE PARTIDOS
    |--------------------------------------------------------------------------
    */
    Route::get('/porras/{id}/pronosticos', [PronosticoController::class, 'index'])
        ->name('porras.pronosticos');

    Route::post('/porras/{id}/pronosticos', [PronosticoController::class, 'store'])
        ->name('pronosticos.store');

    /*
    | Consulta de MIS pronósticos (pantalla 8.10)
    */
    Route::get('/porras/{id}/mis-pronosticos', [PronosticoConsultaController::class, 'misPronosticos'])
        ->name('porras.mis_pronosticos');


    /*
    |--------------------------------------------------------------------------
    | PARTIDOS Y DETALLE
    |--------------------------------------------------------------------------
    */
    Route::get('/porras/{id}/tabla-partidos', [TablaPartidosController::class, 'index'])
        ->name('tabla.partidos');

    Route::get(
        '/porras/{idPorra}/partidos/{idPartido}',
        [PartidoPronosticosController::class, 'show']
    )->name('porras.partido');


    /*
    |--------------------------------------------------------------------------
    | CAMPEONES (PRONÓSTICOS ESPECIALES)
    |--------------------------------------------------------------------------
    */
    Route::get('/porras/{id}/campeones', [CampeonesController::class, 'edit'])
        ->name('porras.campeones');

    Route::post('/porras/{id}/campeones', [CampeonesController::class, 'update'])
        ->name('porras.campeones.update');

    /*
    | Ver pronósticos de campeones del resto de participantes
    | (misma vista, con warning si la competición no ha comenzado)
    */
    Route::get(
        '/porras/{id}/campeones/participantes',
        [CampeonesController::class, 'verCampeonesParticipantes']
    )->name('porras.campeones.participantes');
});

/*
|--------------------------------------------------------------------------
| CONSULTA DE PRONÓSTICOS DE OTRO PARTICIPANTE
|--------------------------------------------------------------------------
| Se mantiene fuera del grupo principal para claridad semántica,
| aunque también requiere usuario autenticado.
| Pantalla 8.11 del ERS.
*/

Route::middleware(['web', 'usuario.auth'])->get(
    '/porras/{idPorra}/participantes/{idUsuario}',
    [PronosticoConsultaController::class, 'pronosticosUsuario']
)->name('porras.participante');



/**
|--------------------------------------------------------------------------
| SOLO PARA PRODUCCION - EJECUCION DEL COMANDO QUE DESCARGA PARTIDOS DEL MUNDIAL DE API EXTERNA
|--------------------------------------------------------------------------
| Creo una ruta especial que usaré solo para poder actualizar los partidos desde el panel de control
| Esto lo hago porque mi hosting gratuito no me permite ejecutar comandos artisan desde la terminal, 
| pero sí puedo acceder a esta ruta para ejecutar el comando que descarga los partidos del mundial desde la API externa.
| 
*/              



use App\Http\Controllers\Admin\ImportarMundialController;

Route::post(
    '/admin/porra/{porra}/importar-mundial',
    [ImportarMundialController::class, 'importar']
)->name('admin.importar-mundial');


use App\Http\Controllers\Admin\RecalcularClasificacionPorraController;

Route::post(
    '/admin/porra/{porra}/recalcular-clasificacion',
    [RecalcularClasificacionPorraController::class, 'recalcular']
)->name('admin.recalcular-clasificacion');






