<?php

namespace App\Middleware;

class RoleMiddleware
{

    public static function handleBorrar(array $rolesPermitidos): void
{
    echo '<pre>';
    var_dump($_SESSION['usuario_roles'] ?? null);
    var_dump($rolesPermitidos);
    echo '</pre>';
    exit;
}

    public static function handle(array $rolesPermitidos): void
    {
        $rolesActuales =
            $_SESSION['usuario_roles'] ?? [];

        if (empty($rolesActuales)) {
            http_response_code(403);
            exit('Acceso denegado');
        }

        foreach ($rolesActuales as $rol) {

            if (
                in_array(
                    $rol,
                    $rolesPermitidos,
                    true
                )
            ) {
                return;
            }
        }

        http_response_code(403);
        exit('No posee permisos suficientes');
    }
}