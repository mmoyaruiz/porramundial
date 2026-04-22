<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class Participacion extends Model
{
    protected $table = 'participaciones';
    protected $primaryKey = 'id_participacion';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_porra',
        'es_admin',
        'puntos',
        'posicion',
    ];
}
