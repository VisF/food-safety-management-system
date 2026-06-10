<?php
declare(strict_types=1);

/**
 * CarnetControlador
 * Gestión de emisión, consulta y anulación de carnets.
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

        $this->carnetService =
            new CarnetService(
                Connection::getPDO()
            );
    }

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
    public function obtenerCarnetPorInscripcion(
        int $idInscripcion
    ): ?array
    {
        try {

            $pdo = Connection::getPDO();

            $stmt = $pdo->prepare(
                "SELECT *
                 FROM carnets
                 WHERE inscripcion_id = :id
                 LIMIT 1"
            );

            $stmt->execute([
                ':id' => $idInscripcion
            ]);

            $carnet =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );

            return $carnet ?: null;

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_CARNET',
                [
                    'id_inscripcion' =>
                        $idInscripcion,
                    'error' =>
                        $e->getMessage()
                ]
            );

            return null;
        }
    }

    /**
     * Obtener carnet por número
     */
    public function obtenerPorNumero(
        string $numeroCarnet
    ): ?array
    {
        try {

            $pdo = Connection::getPDO();

            $stmt = $pdo->prepare(
                "SELECT *
                 FROM carnets
                 WHERE numero_carnet = :numero
                 LIMIT 1"
            );

            $stmt->execute([
                ':numero' => $numeroCarnet
            ]);

            $carnet =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );

            return $carnet ?: null;

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_OBTENER_CARNET_NUMERO',
                [
                    'numero_carnet' =>
                        $numeroCarnet,
                    'error' =>
                        $e->getMessage()
                ]
            );

            return null;
        }
    }

    /**
     * Anular carnet
     */
    public function anularCarnet(
        int $idCarnet
    ): array
    {
        try {

            $pdo = Connection::getPDO();

            $stmt = $pdo->prepare(
                "UPDATE carnets
                 SET activo = 0
                 WHERE id = :id"
            );

            $stmt->execute([
                ':id' => $idCarnet
            ]);

            $this->registrarLog(
                'CARNET_ANULADO',
                [
                    'id_carnet' =>
                        $idCarnet
                ]
            );

            return [
                'success' => true,
                'mensaje' =>
                    'Carnet anulado correctamente'
            ];

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_ANULAR_CARNET',
                [
                    'id_carnet' =>
                        $idCarnet,
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
     * Listar carnets activos
     */
    public function listarActivos(): array
    {
        try {

            $pdo = Connection::getPDO();

            $stmt = $pdo->query(
                "SELECT *
                 FROM carnets
                 WHERE activo = 1
                 ORDER BY fecha_emision DESC"
            );

            return $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );

        } catch (\Exception $e) {

            $this->registrarLog(
                'ERROR_LISTAR_CARNETS',
                [
                    'error' =>
                        $e->getMessage()
                ]
            );

            return [];
        }
    }
}
