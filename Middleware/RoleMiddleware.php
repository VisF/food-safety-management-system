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

        // Cada cuenta debe tener exactamente un rol
        if (
            !is_array($rolesActuales) ||
            count($rolesActuales) !== 1
        ) {
            http_response_code(403);
            exit('Acceso denegado');
        }

        $rolActual = $rolesActuales[0];

        // El rol tiene permiso para acceder a esta ruta
        if (
            in_array(
                $rolActual,
                $rolesPermitidos,
                true
            )
        ) {
            return;
        }

        // Ruta principal correspondiente a cada rol
        $rutasPorRol = [
            'usuario'   => '/',
            'admin'     => '/admin',
            'inspector' => '/consulta-publica'
        ];

        // Rol inexistente o no reconocido
        if (!isset($rutasPorRol[$rolActual])) {
            http_response_code(403);
            exit('Rol no reconocido');
        }

        // Redirigir al espacio correspondiente
        $baseUrl = defined('BASE_URL')
            ? rtrim(BASE_URL, '/')
            : '';

        header(
            'Location: ' .
            $baseUrl .
            $rutasPorRol[$rolActual]
        );

        exit;
    }
}