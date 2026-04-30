<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Invitacion
 *
 * Guarda los datos de las invitaciones enviadas a usuarios para unirse a una porra.
 */


class Invitacion extends Model
{

    protected $table = 'invitaciones';
    protected $primaryKey = 'id_invitacion';
    public $timestamps = false;

    protected $fillable = [
        'id_porra',
        'id_usuario_invitador',
        'usuario_destino',
        'email_destino',
        'estado',
        'fecha_envio',
        'fecha_respuesta',
    ];

    public function porra()
    {
        return $this->belongsTo(Porra::class, 'id_porra', 'id_porra');
    }

    public function invitador()
    {
        return $this->belongsTo(Usuario::class, 'id_usuario_invitador', 'id_usuario');
    }
}


