<?php

namespace App\Middleware;

class MaintenanceMiddleware
{
    private const FILE_PATH =
        __DIR__ . '/../maintenance.flag';

    public static function handle(): void
    {
        if (!file_exists(self::FILE_PATH)) {
            return;
        }

        http_response_code(503);

        echo '
        <h1>Sitio en mantenimiento</h1>
        <p>Intente nuevamente en unos minutos.</p>
        ';

        exit;
    }
}