<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/****
 * Modelo Pronostico
 *
 * Guarda los datos de los pronósticos realizados por los usuarios para cada partido en una porra.
 */

class Pronostico extends Model
{
    protected $table = 'pronosticos';
    protected $primaryKey = 'id_pronostico';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_porra',
        'id_partido',
        'goles_local_pronosticados',
        'goles_visitante_pronosticados',
        'puntos_obtenidos',
        'fecha_creacion',
    ];
}
