<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo PronosticoCampeon
 *
 * Guarda los datos de los pronósticos de campeón de grupo y de competición realizados por los usuarios en una porra.
 */

class PronosticoCampeon extends Model
{
    protected $table = 'pronosticos_campeones';
    protected $primaryKey = 'id_pronostico_campeon';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_porra',
        'tipo_pronostico',     // 'grupo' | 'competicion'
        'grupo',               // 'A','B'... o NULL
        'equipo_pronosticado', // nombre o TLA (según decidas)
        'puntos_obtenidos',
    ];
}
