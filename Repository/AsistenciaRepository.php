<?php
declare(strict_types=1);

/**
 * Repositorio para gestionar asistencias.
 * 
 * 
 * 
 * Metodos:
 * crear() 
 * obtenerPorId()
 * obtenerPorInscripcion()
 * obtenerPorUsuario()
 * obtenerPorCurso()
 * obtenerTotalAsistencias()
 * listar()
 * actualizar()
 * eliminar()
 */



require_once __DIR__ . '/../db/Connection.php';

class AsistenciaRepository
{
    private \PDO $conexion;

    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }

    /**
     * Crear asistencia.
     */
    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO asistencias
            (
                inscripcion_id,
                fecha,
                presente,
                observaciones
            )
            VALUES
            (
                :inscripcion,
                :fecha,
                :presente,
                :observaciones
            )
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':inscripcion'   => $datos['inscripcion_id'],
            ':fecha'         => $datos['fecha'],
            ':presente'      => $datos['presente'],
            ':observaciones' => $datos['observaciones'] ?? null
        ]);

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Obtener asistencias por ID.
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM asistencias
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $fila ?: null;
    }

    /**
     * Obtener asistencias de una inscripción.
     */
    public function obtenerPorInscripcion(int $inscripcionId): array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM asistencias
            WHERE inscripcion_id = :id
            ORDER BY fecha ASC
        ");

        $stmt->execute([
            ':id' => $inscripcionId
        ]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener asistencias de un usuario.
     */
    public function obtenerPorUsuario(int $usuarioId): array
    {
        $stmt = $this->conexion->prepare("
            SELECT
                a.*

            FROM asistencias a

            INNER JOIN inscripciones i
                ON i.id = a.inscripcion_id

            WHERE i.usuario_id = :usuario

            ORDER BY
                a.fecha ASC
        ");

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener asistencias de un curso.
     */
    public function obtenerPorCurso(int $cursoId): array
    {
        $stmt = $this->conexion->prepare("
            SELECT
                a.*

            FROM asistencias a

            INNER JOIN inscripciones i
                ON i.id = a.inscripcion_id

            WHERE i.curso_id = :curso

            ORDER BY
                a.fecha ASC
        ");

        $stmt->execute([
            ':curso' => $cursoId
        ]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

        /**
     * Obtener estadísticas de asistencias de una inscripción.
     */
    public function obtenerTotalAsistencias(int $inscripcionId): array
    {
        $stmt = $this->conexion->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(
                    CASE
                        WHEN presente = 1
                        THEN 1
                        ELSE 0
                    END
                ) AS presentes
            FROM asistencias
            WHERE inscripcion_id = :id
        ");

        $stmt->execute([
            ':id' => $inscripcionId
        ]);

        $fila = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'total' => (int)($fila['total'] ?? 0),
            'presentes' => (int)($fila['presentes'] ?? 0)
        ];
    }

    /**
     * Listar todas las asistencias.
     */
    public function listar(): array
    {
        $stmt = $this->conexion->prepare("
            SELECT
                a.*,

                u.nombre,
                u.apellido,

                c.nombre AS curso

            FROM asistencias a

            INNER JOIN inscripciones i
                ON i.id = a.inscripcion_id

            INNER JOIN usuarios u
                ON u.id = i.usuario_id

            LEFT JOIN cursos c
                ON c.id = i.curso_id

            ORDER BY
                a.fecha DESC
        ");

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar asistencia.
     */
    public function actualizar(
        int $id,
        array $datos
    ): bool {

        $stmt = $this->conexion->prepare("
            UPDATE asistencias
            SET
                fecha = :fecha,
                presente = :presente,
                observaciones = :observaciones
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':fecha' => $datos['fecha'],
            ':presente' => $datos['presente'],
            ':observaciones' =>
                $datos['observaciones'] ?? null
        ]);
    }

    /**
     * Eliminar asistencias.
     */
    public function eliminar(int $id): bool
    {
        $stmt = $this->conexion->prepare("
            DELETE
            FROM asistencias
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id
        ]);
    }
}