<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competicion extends Model
{
    protected $table = 'competiciones';
    protected $primaryKey = 'id_competicion';
    public $timestamps = false;

    protected $fillable = ['nombre', 'fecha_inicio', 'fecha_fin', 'tipo_torneo'];


    public function partidos()
    {
        return $this->hasMany(Partido::class, 'id_competicion', 'id_competicion');
    }
}


