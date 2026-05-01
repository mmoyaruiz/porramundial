<?php

namespace App\Http\Controllers;

use App\Models\Participacion;


/**
 * Controlador base para otros controladores.
 * Incluye métodos para verificar si un usuario es superadmin o administrador de una porra.
 */


class Controller
{
    /**
     * Devuelve true si el usuario de sesión es el superadmin global.
     */
    protected function isSuperAdmin($usuario): bool
    {
        return $usuario && $usuario->correo_electronico === config('porramundial.superadmin_email');
    }

    /**
     * Devuelve true si el usuario es administrador de la porra
     */
    protected function isAdminDePorra($usuario, int $idPorra): bool
    {
        if ($this->isSuperAdmin($usuario)) {
            return true;
        }

        return Participacion::where('id_usuario', $usuario->id_usuario)
            ->where('id_porra', $idPorra)
            ->where('es_admin', 1)
            ->exists();
    }
}


