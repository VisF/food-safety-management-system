<?php
declare(strict_types=1);

/**
 * Métodos:
 * - listarExamenes()
 * - contarExamenes()
 * - obtenerExamen()
 * - crearExamen()
 * - actualizarExamen()
 * - activarExamen()
 * - desactivarExamen()
 * - actualizarCupos()
 * - obtenerProximos()
 * - contarInscriptos()
 */

require_once __DIR__ . '/../db/Connection.php';

class ExamenRepository
{
    private \PDO $conexion;

    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }
    public function listarExamenes(): array
    {
        $sql = "
            SELECT
                id,
                fecha,
                hora,
                ubicacion,
                aula,
                cupos,
                activo
            FROM examenes
            ORDER BY fecha DESC, hora DESC
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function contarExamenes(): int
    {
        $stmt = $this->conexion->query("
            SELECT COUNT(*)
            FROM examenes
        ");

        return (int)$stmt->fetchColumn();
    }

    public function obtenerExamen(int $id): ?array
    {
        $sql = "
            SELECT
                id,
                fecha,
                hora,
                ubicacion,
                aula,
                cupos,
                activo
            FROM examenes
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(
            ':id',
            $id,
            \PDO::PARAM_INT
        );

        $stmt->execute();

        $examen = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $examen ?: null;
    }



    public function crearExamen(array $datos): int
    {
        $sql = "
            INSERT INTO examenes
            (
                fecha,
                hora,
                ubicacion,
                aula,
                cupos,
                activo
            )
            VALUES
            (
                :fecha,
                :hora,
                :ubicacion,
                :aula,
                :cupos,
                1
            )
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([

            ':fecha'      => $datos['fecha'],

            ':hora'       => $datos['hora'],

            ':ubicacion'  => $datos['ubicacion'],

            ':aula'       => $datos['aula'],

            ':cupos'      => (int)$datos['cupos']

        ]);

        return (int)$this->conexion->lastInsertId();
    }



    public function actualizarExamen(int $id,array $datos): bool
    {
        $sql = "
            UPDATE examenes
            SET
                fecha = :fecha,
                hora = :hora,
                ubicacion = :ubicacion,
                aula = :aula,
                cupos = :cupos,
                activo = :activo
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([

            ':id'         => $id,

            ':fecha'      => $datos['fecha'],

            ':hora'       => $datos['hora'],

            ':ubicacion'  => $datos['ubicacion'],

            ':aula'       => $datos['aula'],

            ':cupos'      => (int)$datos['cupos'],

            ':activo'     => (int)$datos['activo']

        ]);
    }
    public function activarExamen(int $id): bool
    {
        $stmt = $this->conexion->prepare("
            UPDATE examenes
            SET activo = 1
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id
        ]);
    }
    public function desactivarExamen(int $id): bool
    {
        $stmt = $this->conexion->prepare("
            UPDATE examenes
            SET activo = 0
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id
        ]);
    }

    public function actualizarCupos(int $id,int $cupos): bool
    {
        $stmt = $this->conexion->prepare("
            UPDATE examenes
            SET cupos = :cupos
            WHERE id = :id
        ");

        return $stmt->execute([
            ':cupos' => $cupos,
            ':id' => $id
        ]);
    }

    public function obtenerProximos(int $cantidad = 5): array
    {
        $sql = "
            SELECT
                id,
                fecha,
                hora,
                ubicacion,
                aula,
                cupos
            FROM examenes
            WHERE activo = 1
            AND fecha >= CURDATE()
            ORDER BY fecha ASC, hora ASC
            LIMIT :cantidad
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(
            ':cantidad',
            $cantidad,
            \PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
