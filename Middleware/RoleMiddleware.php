<?php

namespace App\Middleware;

class RoleMiddleware
{
    public static function handle(array $rolesPermitidos): void
    {
        $rolActual =
            $_SESSION['usuario_roles'] ?? null;

        if ($rolActual === null) {
            http_response_code(403);
            exit('Acceso denegado');
        }

        if (
            !in_array(
                $rolActual,
                $rolesPermitidos,
                true
            )
        ) {
            http_response_code(403);
            exit('No posee permisos suficientes');
        }
    }
}