<?php

namespace App\Middleware;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (
            !isset($_SESSION['usuario_id']) ||
            empty($_SESSION['usuario_id'])
        ) {
            header('Location: /manipulacionDeAlimentos/login');
            exit;
        }
    }
}