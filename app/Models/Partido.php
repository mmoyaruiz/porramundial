<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Partido
 *
 * Guarda los datos de los partidos de la competición, incluyendo equipos, resultados y estado.
 */

class Partido extends Model
{
    protected $table = 'partidos';
    protected $primaryKey = 'id_partido';
    public $timestamps = false;


    protected $fillable = [
        'api_match_id',
        'id_competicion',
        'fecha_hora',
        'estado',
        'fase',
        'grupo',
        'equipo_local_nombre',
        'equipo_local_shortname',
        'equipo_local_tla',
        'equipo_local_crest_url',
        'equipo_visitante_nombre',
        'equipo_visitante_shortname',
        'equipo_visitante_tla',
        'equipo_visitante_crest_url',
        'goles_local',
        'goles_visitante',
    ];

    public static function competicionHaComenzado(int $idCompeticion): bool
    {
        return self::where('id_competicion', $idCompeticion)
            ->whereIn('estado', ['en_juego', 'finalizado'])
            ->exists();
    }
}
