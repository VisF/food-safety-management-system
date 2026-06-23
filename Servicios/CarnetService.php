<?php
declare(strict_types=1);

/**
 * CarnetService
 * Lógica de negocio relacionada con carnets.
 */

class CarnetService
{
    private CarnetModelo $carnetModelo;
    private ?PDO $conexion = null;

    public function __construct(?CarnetModelo $carnetModelo = null)
    {
        $this->carnetModelo = $carnetModelo ?? new CarnetModelo();

        $connFile = __DIR__ . '/../db/Connection.php';
        if (file_exists($connFile)) {
            require_once $connFile;
            $this->conexion = Connection::getPDO();
        }
    }

    /**
     * Emitir carnet para una inscripción aprobada
     */
   public function emitirCarnet(int $idInscripcion, string $rutaPdf): array
    {
        try {

            if (!$this->conexion) {
                return [
                    'success' => false,
                    'mensaje' => 'No hay conexión a la base de datos'
                ];
            }

            // Verificar que exista la inscripción
            $stmt = $this->conexion->prepare(
                'SELECT *
                FROM inscripciones
                WHERE id = :id'
            );

            $stmt->execute([
                ':id' => $idInscripcion
            ]);

            $inscripcion = $stmt->fetch();

            if (!$inscripcion) {
                return [
                    'success' => false,
                    'mensaje' => 'La inscripción no existe'
                ];
            }

            // La inscripción debe estar en estado aprobado
            if ((int)$inscripcion['estado_tramite_id'] !== EstadoTramite::APROBADO) {
                return [
                    'success' => false,
                    'mensaje' => 'La inscripción no se encuentra en estado aprobado'
                ];
            }

            // Verificar que no exista carnet previo
            $existente = $this->carnetModelo->obtenerPorInscripcion(
                $idInscripcion
            );

            if ($existente) {
                return [
                    'success' => false,
                    'mensaje' => 'La inscripción ya posee un carnet'
                ];
            }

            // Verificar resultado aprobado
            $stmt = $this->conexion->prepare(
                'SELECT *
                FROM resultado_examen
                WHERE inscripcion_id = :id
                AND aprobado = 1'
            );

            $stmt->execute([
                ':id' => $idInscripcion
            ]);

            $resultado = $stmt->fetch();

            if (!$resultado) {
                return [
                    'success' => false,
                    'mensaje' => 'La inscripción no posee un examen aprobado'
                ];
            }

            $numeroCarnet =
                'MA-' .
                date('Y') .
                '-' .
                strtoupper(substr(md5(uniqid('', true)), 0, 8));

            $fechaEmision = date('Y-m-d');

            $fechaVencimiento = date(
                'Y-m-d',
                strtotime('+3 years')
            );

            $carnet = $this->carnetModelo->crear([
                'id_inscripcion' => $idInscripcion,
                'numero_carnet' => $numeroCarnet,
                'fecha_emision' => $fechaEmision,
                'fecha_vencimiento' => $fechaVencimiento,
                'ruta_pdf' => $rutaPdf
            ]);

            if ($carnet === false) {
                return [
                    'success' => false,
                    'mensaje' => 'No se pudo crear el carnet'
                ];
            }

            // 8 = carnet_emitido
            $stmt = $this->conexion->prepare(
                'UPDATE inscripciones
                SET estado_tramite_id = :estado
                WHERE id = :id'
            );

            $stmt->execute([
                ':estado' => EstadoTramite::CARNET_EMITIDO,
                ':id' => $idInscripcion
            ]);

            return [
                'success' => true,
                'mensaje' => 'Carnet emitido correctamente',
                'carnet' => $carnet
            ];

        } catch (\Exception $e) {

            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }
    /**
     * Obtener carnet por inscripción
     */
    public function obtenerPorInscripcion(
        int $idInscripcion
    ): ?array
    {
        return $this->carnetModelo
            ->obtenerPorInscripcion(
                $idInscripcion
            );
    }

    /**
     * Obtener carnet por DNI
     */
    public function obtenerPorDNI(
        string $dni
    ): ?array
    {
        return $this->carnetModelo
            ->obtenerPorDNI(
                $dni
            );
    }

    /**
     * Renovar carnet
     */
    public function renovarCarnet(
        int $idCarnet,
        string $nuevaFechaVencimiento,
        string $nuevaRutaPdf
    ): bool
    {
        return $this->carnetModelo->renovar(
            $idCarnet,
            $nuevaFechaVencimiento,
            $nuevaRutaPdf
        );
    }

    /**
     * Verificar vigencia
     */
    public function verificarVigencia(
        int $idCarnet
    ): bool
    {
        return $this->carnetModelo
            ->verificarVigencia(
                $idCarnet
            );
    }

    /**
     * Obtener carnets vencidos
     */
    public function obtenerCarnetsVencidos(): array
    {
        return $this->carnetModelo
            ->obtenerCarnetesVencidos();
    }
}