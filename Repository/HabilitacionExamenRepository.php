<?php
declare(strict_types=1);


/**
 * HabilitacionExamenRepository - Repositorio del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

require_once __DIR__ . '/../db/Connection.php';
require_once __DIR__ . '/../dto/HabilitacionExamenDTO.php';

class HabilitacionExamenRepository
{
    private \PDO $conexion;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }

    /**
     * Crear una habilitación.
     */
    public function crear(array $datos): bool
    {
        $sql = "
            INSERT INTO habilitaciones_examen
            (
                usuario_id,
                curso_id,
                fecha_habilitacion,
                fecha_vencimiento,
                activa
            )
            VALUES
            (
                :usuario_id,
                :curso_id,
                :fecha_habilitacion,
                :fecha_vencimiento,
                1
            )
        ";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':usuario_id' => $datos['usuario_id'],
            ':curso_id' => $datos['curso_id'],
            ':fecha_habilitacion' => $datos['fecha_habilitacion'],
            ':fecha_vencimiento' => $datos['fecha_vencimiento']
        ]);
    }

    /**
     * Obtener la habilitación activa de un usuario.
     */
    public function obtenerActivaPorUsuario(
        int $usuarioId
    ): ?HabilitacionExamenDTO {

        $sql = "
            SELECT *
            FROM habilitaciones_examen
            WHERE usuario_id = :usuario
            AND activa = 1
            AND fecha_vencimiento >= CURDATE()
            ORDER BY fecha_habilitacion DESC
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        $fila =
            $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$fila) {
            return null;
        }

        return new HabilitacionExamenDTO(
            (int)$fila['id'],
            (int)$fila['usuario_id'],
            isset($fila['curso_id'])
                ? (int)$fila['curso_id']
                : null,
            new DateTime(
                $fila['fecha_habilitacion']
            ),
            new DateTime(
                $fila['fecha_vencimiento']
            ),
            (bool)$fila['activa']
        );
    }

    /**
     * Verificar si un usuario tiene
     * una habilitación vigente.
     */
    public function tieneHabilitacionVigente(
        int $usuarioId
    ): bool {

        return
            $this->obtenerActivaPorUsuario(
                $usuarioId
            ) !== null;
    }

    /**
     * Vencer automáticamente
     * habilitaciones expiradas.
     */
    public function vencer(): bool
    {
        $stmt = $this->conexion->prepare("
            UPDATE habilitaciones_examen
            SET activa = 0
            WHERE activa = 1
            AND fecha_vencimiento < CURDATE()
        ");

        return $stmt->execute();
    }

    /**
     * Desactivar una habilitación.
     */
    public function desactivar(
        int $id
    ): bool {

        $stmt = $this->conexion->prepare("
            UPDATE habilitaciones_examen
            SET activa = 0
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id
        ]);
    }
}
