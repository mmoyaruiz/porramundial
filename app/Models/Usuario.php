<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Porra;
use App\Models\Participacion;
use App\Models\Partido;
use App\Models\Pronostico;

//use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Modelo Usuario
 *
 * Representa la tabla 'usuarios' de la base de datos.
 * Se extiende de Authenticatable para poder usar
 * el sistema de autenticación de Laravel SIN Breeze ni Node.
 *
 */
class Usuario extends Model
{
    /**
     * Nombre real de la tabla en la base de datos
     */
    protected $table = 'usuarios';

    /**
     * Clave primaria personalizada
     */
    protected $primaryKey = 'id_usuario';

    /**
     * Indica que la PK es autoincremental
     */
    public $incrementing = true;

    /**
     * Tipo de la clave primaria
     */
    protected $keyType = 'int';

    /**
     * Laravel NO debe manejar timestamps automáticos
     * porque usamos 'fecha_registro'
     */
    public $timestamps = false;

    /**
     * Campos que se pueden asignar de forma masiva
     */
    protected $fillable = [
        'nombre_usuario',
        'correo_electronico',
        'password_hash',
        'es_activo'
    ];

    /**
     * Campo que Laravel usará como contraseña
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Devuelve las porras en las que el usuario participa.
     * Se usa en el panel principal.
     */
    public function porrasParticipa()
    {

        return Porra::select('porras.*')
            ->join('participaciones', 'participaciones.id_porra', '=', 'porras.id_porra')
            ->where('participaciones.id_usuario', $this->id_usuario)
            ->orderByDesc('porras.fecha_creacion');
    }

    /**
     * Devuelve las porras que el usuario administra.
     */

    public function porrasAdministra()
    {
        return Porra::select('porras.*')
            ->join('participaciones', 'participaciones.id_porra', '=', 'porras.id_porra')
            ->where('participaciones.id_usuario', $this->id_usuario)
            ->where('participaciones.es_admin', 1)
            ->orderByDesc('porras.fecha_creacion');
    }

    /**
     * Cuenta los partidos futuros para los que el usuario
     * aún NO ha enviado pronóstico.
     */

    public function partidosPendientesPronostico(): int
    {

        // 1. Porras en las que participa
        $idsPorras = Participacion::where('id_usuario', $this->id_usuario)
            ->pluck('id_porra');

        if ($idsPorras->isEmpty()) {
            return 0;
        }

        // 2. Competiciones asociadas a esas porras
        $idsCompeticiones = Porra::whereIn('id_porra', $idsPorras)
            ->pluck('id_competicion');

        if ($idsCompeticiones->isEmpty()) {
            return 0;
        }

        // 3. Partidos futuros de esas competiciones
        $partidosFuturos = Partido::whereIn('id_competicion', $idsCompeticiones)
            ->where('fecha_hora', '>', now())
            ->pluck('id_partido');

        if ($partidosFuturos->isEmpty()) {
            return 0;
        }

        // 4. Partidos ya pronosticados por el usuario
        $pronosticados = Pronostico::where('id_usuario', $this->id_usuario)
            ->whereIn('id_partido', $partidosFuturos)
            ->pluck('id_partido');

        // 5. Diferencia = pendientes
        return $partidosFuturos->diff($pronosticados)->count();
    }
}
