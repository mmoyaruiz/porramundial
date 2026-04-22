<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CampeonReal
 * - Guarda campeones reales de una competición (por grupo y campeón del torneo).
 * - Se usa para puntuar pronosticos_campeones en cada porra.
 */
class CampeonReal extends Model
{
    protected $table = 'campeones_reales';
    protected $primaryKey = 'id_campeon_real';
    public $timestamps = false;

    protected $fillable = [
        'id_competicion',
        'tipo',       // 'grupo' | 'competicion'
        'grupo',      // 'A','B'... o '' si tipo='competicion'
        'equipo_tla', // 'ESP', 'BRA', ...
        'equipo_shortname',
        'fuente',
    ];
}
