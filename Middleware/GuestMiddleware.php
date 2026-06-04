<?php

namespace App\Middleware;

class GuestMiddleware
{
    public static function handle(): void
    {
        if (
            isset($_SESSION['usuario_id']) &&
            !empty($_SESSION['usuario_id'])
        ) {
            header(
                'Location: Router.php?r=index'
            );

            exit;
        }
    }
}