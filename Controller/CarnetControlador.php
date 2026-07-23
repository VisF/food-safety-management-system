<?php
declare(strict_types=1);


/**
 * CarnetControlador - Controlador del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * CarnetControlador
 * Gestión de emisión, consulta y anulación de carnets.
 */

class CarnetControlador
{
    private CarnetService $carnetService;

    private const LOG_FILE =
        __DIR__ . '/../logs/carnet_controller.log';

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        require_once __DIR__ . '/../db/Connection.php';
        require_once __DIR__ . '/../Servicios/CarnetService.php';

        @mkdir(dirname(self::LOG_FILE), 0755, true);

        $this->carnetService =
            new CarnetService();
    }

    // Registra log.
    private function registrarLog(
        string $evento,
        array $datos = []
    ): void
    {
        $timestamp = date('Y-m-d H:i:s');

        $usuario =
            $_SESSION['user_id']
            ?? 'anonimo';

        $mensaje =
            sprintf(
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
     * Emitir carnet
     */
    public function emitirCarnet(
        int $idInscripcion
    ): array
    {
        try {

            $resultado =
                $this->carnetService
                    ->emitirCarnet(
                        $idInscripcion
                    );

            $this->registrarLog(
                'CARNET_EMITIDO',
                [
                    'id_inscripcion' =>
                        $idInscripcion,
                    'resultado' =>
                        $resultado
                ]
            );

            return $resultado;

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_EMITIR_CARNET',
                [
                    'id_inscripcion' =>
                        $idInscripcion,
                    'error' =>
                        $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'mensaje' =>
                    $e->getMessage()
            ];
        }
    }

    /**
     * Obtener carnet por inscripción
     */
    public function obtenerCarnetPorInscripcion(int $idInscripcion): ?array
    {
        try {

            return $this->carnetService
                ->obtenerPorInscripcionId(
                    $idInscripcion
                );

        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_OBTENER_CARNET',
                [
                    'id_inscripcion' => $idInscripcion,
                    'error' => $e->getMessage()
                ]
            );

            return null;
        }
    }

    /**
     * Obtener carnet por número
     */
    public function obtenerPorNumero(string $numeroCarnet): ?array
    {
        try {

            return $this->carnetService
                ->obtenerPorNumero(
                    $numeroCarnet
                );

        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_OBTENER_CARNET_NUMERO',
                [
                    'numero_carnet' => $numeroCarnet,
                    'error' => $e->getMessage()
                ]
            );

            return null;
        }
    }

    /**
     * Anular carnet
     */
    public function anularCarnet(int $idCarnet): array
    {
        try {

            $resultado = $this->carnetService
                ->anularCarnet($idCarnet);

            $this->registrarLog(
                'CARNET_ANULADO',
                [
                    'id_carnet' => $idCarnet
                ]
            );

            return $resultado;

        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_ANULAR_CARNET',
                [
                    'id_carnet' => $idCarnet,
                    'error' => $e->getMessage()
                ]
            );

            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }

    /**
     * Listar carnets activos
     */
    public function listarActivos(): array
    {
        try {

            return $this->carnetService
                ->listarActivos();

        } catch (Throwable $e) {

            $this->registrarLog(
                'ERROR_LISTAR_CARNETS',
                [
                    'error' => $e->getMessage()
                ]
            );

            return [];
        }
    }
}
