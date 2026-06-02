<?php

namespace App\Middleware;

class CsrfMiddleware
{
    public static function generateToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] =
                bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function validate(): void
    {
        $token = $_POST['csrf_token'] ?? '';

        if (
            !isset($_SESSION['csrf_token']) ||
            !hash_equals(
                $_SESSION['csrf_token'],
                $token
            )
        ) {
            http_response_code(419);
            exit('Token CSRF inválido');
        }
    }
}