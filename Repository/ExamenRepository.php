<?php
declare(strict_types=1);


/**
 * ExamenRepository - Repositorio del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

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
 * - descontarCupo()
 * - obtenerDetalleExamen()
 * - obtenerDisponibles()
 * - obtenerProximosPorUsuario()
 * - obtenerAprobados()
 */

require_once __DIR__ . '/../db/Connection.php';

class ExamenRepository
{
    private \PDO $conexion;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }
    // Lista examenes.
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

    // Ejecuta contar examenes.
    public function contarExamenes(): int
    {
        $stmt = $this->conexion->query("
            SELECT COUNT(*)
            FROM examenes
        ");

        return (int)$stmt->fetchColumn();
    }

    // Obtiene examen.
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



    // Crea examen.
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



    // Actualiza examen.
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
    // Ejecuta activar examen.
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
    // Ejecuta desactivar examen.
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

    // Actualiza cupos.
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
    // Obtiene proximos.
    public function obtenerProximos(int $dias = 30): array
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
            AND fecha BETWEEN CURDATE()
                        AND DATE_ADD(CURDATE(), INTERVAL :dias DAY)
            ORDER BY fecha ASC, hora ASC
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(
            ':dias',
            $dias,
            \PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    /**
     * Descuenta un cupo disponible.
     */
    public function descontarCupo(int $idExamen): bool
    {
        $stmt = $this->conexion->prepare("
            UPDATE examenes
            SET cupos = GREATEST(cupos - 1, 0)
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $idExamen
        ]);
    }
    /**
     * Obtener detalle completo de un examen.
     */
    public function obtenerDetalleExamen(int $id): ?array
    {
        $sql = "
            SELECT
                e.*,
                COUNT(i.id) AS total_inscriptos
            FROM examenes e
            LEFT JOIN inscripciones i
                ON i.examen_id = e.id
            WHERE e.id = :id
            GROUP BY e.id
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        $cupos = (int)$row['cupos'];

        return [
            'id' => (int)$row['id'],
            'fecha' => $row['fecha'],
            'hora' => $row['hora'],
            'ubicacion' => $row['ubicacion'],
            'aula' => $row['aula'],
            'cupos_totales' => $cupos,
            'cupos_disponibles' => max(
                0,
                $cupos - (int)$row['total_inscriptos']
            ),
            'total_inscriptos' => (int)$row['total_inscriptos'],
            'activo' => (int)$row['activo']
        ];
    }
    /**
     * Obtener exámenes con cupos disponibles.
     */
    public function obtenerDisponibles(): array
    {
        $sql = "
            SELECT
                e.*,
                (e.cupos - COUNT(i.id)) AS cupos_libres
            FROM examenes e

            LEFT JOIN inscripciones i
                ON i.examen_id = e.id

            WHERE e.activo = 1
            AND e.fecha >= CURDATE()

            GROUP BY e.id

            HAVING cupos_libres > 0

            ORDER BY e.fecha ASC,
                    e.hora ASC
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    /**
     * Obtener próximos exámenes de un usuario.
     */
    public function obtenerProximosPorUsuario(int $usuarioId): array
    {
        $sql = "
            SELECT DISTINCT
                e.*
            FROM examenes e

            INNER JOIN inscripciones i
                ON i.examen_id = e.id

            WHERE i.usuario_id = :usuario
            AND e.fecha >= CURDATE()
            AND i.estado_tramite_id NOT IN
            (
                :aprobado,
                :cancelado
            )

            ORDER BY
                e.fecha ASC,
                e.hora ASC
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(':usuario', $usuarioId, \PDO::PARAM_INT);
        $stmt->bindValue(':aprobado', EstadoTramite::APROBADO, \PDO::PARAM_INT);
        $stmt->bindValue(':cancelado', EstadoTramite::CANCELADO, \PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    /**
     * Obtener usuarios aprobados de un examen.
     */
    public function obtenerAprobados(int $idExamen): array
    {
        $sql = "
            SELECT
                u.*,
                re.nota,
                re.fecha AS fecha_resultado

            FROM resultado_examen re

            INNER JOIN inscripciones i
                ON i.id = re.id_inscripcion

            INNER JOIN usuarios u
                ON u.id = i.usuario_id

            WHERE re.id_examen = :id_examen
            AND re.aprobado = 1

            ORDER BY
                re.fecha DESC
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(
            ':id_examen',
            $idExamen,
            \PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
