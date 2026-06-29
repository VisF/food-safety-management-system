<?php
declare(strict_types=1);


class HabilitacionExamenModelo
{
    private ?PDO $conexion = null;

    public function __construct()
    {
        require_once __DIR__ . '/../db/Connection.php';

        $this->conexion = Connection::getPDO();
    }
    public function crear(array $datos): bool
    {
        if (!$this->conexion) {
            return false;
        }

        $sql = '
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
        ';

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':usuario_id' => $datos['usuario_id'],
            ':curso_id' => $datos['curso_id'],
            ':fecha_habilitacion' => $datos['fecha_habilitacion'],
            ':fecha_vencimiento' => $datos['fecha_vencimiento']
        ]);
    }
    private function obtenerFilaPorUsuario(int $usuarioId): ?array
    {
        if (!$this->conexion) {
            return null;
        }

        $sql = '
            SELECT *
            FROM habilitaciones_examen
            WHERE usuario_id = :usuario_id
            AND activa = 1
            AND fecha_vencimiento >= CURDATE()
            ORDER BY fecha_habilitacion DESC
            LIMIT 1
        ';

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':usuario_id' => $usuarioId
        ]);

        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $fila ?: null;
    }
    public function vencer(): bool
    {
        if (!$this->conexion) {
            return false;
        }

        $sql = '
            UPDATE habilitaciones_examen
            SET activa = 0
            WHERE activa = 1
            AND fecha_vencimiento < CURDATE()
        ';

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute();
    }
    public function desactivar(int $id): bool
    {
        if (!$this->conexion) {
            return false;
        }

        $sql = '
            UPDATE habilitaciones_examen
            SET activa = 0
            WHERE id = :id
        ';

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':id' => $id
        ]);
    }
    public function tieneHabilitacionVigente(int $usuarioId): bool
    {
        return
            $this->obtenerActivaPorUsuario(
                $usuarioId
            ) !== null;
    }
    public function obtenerActivaPorUsuario(int $usuarioId): ?HabilitacionExamenDTO{
        
        $fila = $this->obtenerFilaPorUsuario($usuarioId);

        if ($fila === null) {
            return null;
        }

        return new HabilitacionExamenDTO(
            (int)$fila['id'],
            (int)$fila['usuario_id'],
            isset($fila['curso_id']) ? (int)$fila['curso_id'] : null,
            new DateTime($fila['fecha_habilitacion']),
            new DateTime($fila['fecha_vencimiento']),
            (bool)$fila['activa']
        );
    }
}