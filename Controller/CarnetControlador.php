<?php

declare(strict_types=1);

/**
 * CarnetControlador
 * Gestión de funcionalidades del carnet para el ciudadano.
 */

class CarnetControlador
{
    private CarnetService $carnetService;

    private const LOG_FILE =
        __DIR__ . '/../logs/carnet_controller.log';

    public function __construct()
    {
        require_once __DIR__ . '/../db/Connection.php';
        require_once __DIR__ . '/../Servicios/CarnetService.php';

        @mkdir(dirname(self::LOG_FILE), 0755, true);

        $this->carnetService = new CarnetService();
    }

    private function registrarLog(
        string $evento,
        array $datos = []
    ): void {
        $timestamp = date('Y-m-d H:i:s');

        $usuario =
            $_SESSION['usuario_id']
            ?? 'anonimo';

        $mensaje = sprintf(
            "[%s] Usuario: %s | Evento: %s | Datos: %s\n",
            $timestamp,
            $usuario,
            $evento,
            json_encode(
                $datos,
                JSON_UNESCAPED_UNICODE
            )
        );

        @file_put_contents(
            self::LOG_FILE,
            $mensaje,
            FILE_APPEND
        );
    }

    /**
     * Descarga el carnet del usuario autenticado.
     */
    public function descargarCarnet(): void
    {
        $usuarioId =
            (int) (
                $_SESSION['usuario_id']
                ?? 0
            );

        if ($usuarioId <= 0) {
            http_response_code(403);

            exit(
                'No autorizado.'
            );
        }

        try {
            $carnet =
                $this->carnetService
                    ->obtenerUltimoCarnetUsuario(
                        $usuarioId
                    );

            if (
                empty($carnet)
                ||
                empty($carnet['ruta_pdf'])
            ) {
                http_response_code(404);

                exit(
                    'No se encontró un carnet disponible.'
                );
            }

            $rutaRelativa =
                ltrim(
                    (string) $carnet['ruta_pdf'],
                    '/\\'
                );

            $rutaArchivo =
                realpath(
                    __DIR__ .
                    '/../' .
                    $rutaRelativa
                );

            $directorioCarnets =
                realpath(
                    __DIR__ .
                    '/../uploads/carnets'
                );

            if (
                $rutaArchivo === false
                ||
                $directorioCarnets === false
                ||
                strpos(
                    $rutaArchivo,
                    $directorioCarnets .
                    DIRECTORY_SEPARATOR
                ) !== 0
                ||
                !is_file($rutaArchivo)
                ||
                !is_readable($rutaArchivo)
            ) {
                $this->registrarLog(
                    'PDF_CARNET_NO_DISPONIBLE',
                    [
                        'usuario_id' =>
                            $usuarioId,
                        'id_carnet' =>
                            $carnet['id'] ?? null
                    ]
                );

                http_response_code(404);

                exit(
                    'El archivo del carnet no está disponible.'
                );
            }

            $nombreArchivo =
                'carnet_' .
                preg_replace(
                    '/[^a-zA-Z0-9_-]/',
                    '_',
                    (string) (
                        $carnet['numero_carnet']
                        ?? $carnet['id']
                    )
                ) .
                '.pdf';

            $this->registrarLog(
                'CARNET_DESCARGADO',
                [
                    'usuario_id' =>
                        $usuarioId,
                    'id_carnet' =>
                        $carnet['id'] ?? null
                ]
            );

            header(
                'Content-Type: application/pdf'
            );

            header(
                'Content-Disposition: attachment; filename="' .
                $nombreArchivo .
                '"'
            );

            header(
                'Content-Length: ' .
                filesize($rutaArchivo)
            );

            header(
                'Cache-Control: private, no-store, no-cache, must-revalidate'
            );

            header(
                'Pragma: no-cache'
            );

            header(
                'Expires: 0'
            );

            readfile($rutaArchivo);

            exit;
        } catch (Throwable $e) {
            $this->registrarLog(
                'ERROR_DESCARGAR_CARNET',
                [
                    'usuario_id' =>
                        $usuarioId,
                    'error' =>
                        $e->getMessage()
                ]
            );

            http_response_code(500);

            exit(
                'No fue posible descargar el carnet.'
            );
        }
    }
}