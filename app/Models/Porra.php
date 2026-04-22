<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Porra
 *
 * Tabla: porras
 * PK: id_porra
 * No usamos timestamps de Laravel (la tabla ya tiene fecha_creacion).
 */
class Porra extends Model
{
    protected $table = 'porras';
    protected $primaryKey = 'id_porra';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'id_competicion',
        'id_usuario_creador',
        'es_publica',
        'max_participantes',
        'puntos_ganador',
        'puntos_marcador',
        'puntos_campeon_grupo',
        'puntos_ganador_torneo',
        'estado',
    ];

    /** Usuario creador (FK id_usuario_creador -> usuarios.id_usuario) */
    public function creador()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_creador', 'id_usuario');
    }

    /** Competición base (FK id_competicion -> competiciones.id_competicion) */
    public function competicion()
    {
        return $this->belongsTo(Competicion::class, 'id_competicion', 'id_competicion');
    }

    /** Participaciones (tabla participaciones) */
    public function participaciones()
    {
        return $this->hasMany(Participacion::class, 'id_porra', 'id_porra');
    }

    /** Invitaciones (tabla invitaciones) */
    public function invitaciones()
    {
        return $this->hasMany(Invitacion::class, 'id_porra', 'id_porra');
    }
}

   