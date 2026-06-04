<?php

class AuthHelper
{
    public static function usuarioActual(): ?array
    {
        if (empty($_SESSION['usuario_id'])) {
            return null;
        }

        return [
            'id' => $_SESSION['usuario_id'],
            'nombre' => $_SESSION['usuario_nombre'] ?? '',
            'email' => $_SESSION['usuario_email'] ?? '',
            'rol' => $_SESSION['usuario_rol'] ?? 'usuario'
        ];
    }

    public static function estaAutenticado(): bool
    {
        return !empty($_SESSION['usuario_id']);
    }
}