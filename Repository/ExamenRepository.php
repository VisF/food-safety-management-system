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
                e.id,
                e.fecha,
                e.hora,
                e.ubicacion AS lugar,
                e.aula,
                e.cupos AS cupos_totales,
                e.cupos AS cupos_disponibles,
                CASE
                    WHEN e.activo = 1 THEN 'ACTIVO'
                    ELSE 'INACTIVO'
                END AS estado
            FROM examenes e
            ORDER BY
                e.fecha DESC,
                e.hora DESC
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function listarExamenesProgramadosAscendente(): array
    {
        $sql = "
            SELECT
                id,
                fecha,
                hora,
                ubicacion AS lugar,
                aula,
                cupos AS cupos_totales,
                (
                    cupos -
                    (
                        SELECT COUNT(*)
                        FROM inscripciones i
                        WHERE i.examen_id = examenes.id
                    )
                ) AS cupos_disponibles,
                CASE
                    WHEN activo = 1 THEN 'ACTIVO'
                    ELSE 'INACTIVO'
                END AS estado
            FROM examenes
            WHERE TIMESTAMP(fecha, hora) >= NOW()
            ORDER BY fecha ASC, hora ASC
        ";
        return $this->conexion
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listarExamenesProgramadosDescendente(): array
    {
        $sql = "
            SELECT
                id,
                fecha,
                hora,
                ubicacion AS lugar,
                aula,
                cupos AS cupos_totales,
                (
                    cupos -
                    (
                        SELECT COUNT(*)
                        FROM inscripciones i
                        WHERE i.examen_id = examenes.id
                    )
                ) AS cupos_disponibles,
                CASE
                    WHEN activo = 1 THEN 'ACTIVO'
                    ELSE 'INACTIVO'
                END AS estado
            FROM examenes
            WHERE TIMESTAMP(fecha, hora) >= NOW()
            ORDER BY fecha DESC, hora DESC
        ";
        return $this->conexion
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }
    public function listarHistorialExamenesDescendente(): array
    {
        $sql = "
            SELECT
                id,
                fecha,
                hora,
                ubicacion AS lugar,
                aula,
                cupos AS cupos_totales,
                (
                    cupos -
                    (
                        SELECT COUNT(*)
                        FROM inscripciones i
                        WHERE i.examen_id = examenes.id
                    )
                ) AS cupos_disponibles,
                CASE
                    WHEN activo = 1 THEN 'ACTIVO'
                    ELSE 'INACTIVO'
                END AS estado
            FROM examenes
            WHERE TIMESTAMP(fecha, hora) < NOW()
            ORDER BY fecha DESC, hora DESC
        ";

        return $this->conexion
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
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



    /**
     * Actualiza un examen.
     */
    public function actualizarExamen(
        int $id,
        array $datos
    ): bool
    {
        $sql = "
            UPDATE examenes
            SET
                fecha = :fecha,
                hora = :hora,
                ubicacion = :ubicacion,
                aula = :aula,
                cupos = :cupos
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(
            ':fecha',
            $datos['fecha']
        );

        $stmt->bindValue(
            ':hora',
            $datos['hora']
        );

        $stmt->bindValue(
            ':ubicacion',
            $datos['ubicacion']
        );

        $stmt->bindValue(
            ':aula',
            $datos['aula']
        );

        $stmt->bindValue(
            ':cupos',
            $datos['cupos'],
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }
    
    /**
    * Actualiza el estado de un examen.
    */
    public function actualizarEstado(
        int $id,
        bool $activo
    ): bool
    {
        $sql = "
            UPDATE examenes
            SET activo = :activo
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(
            ':activo',
            $activo ? 1 : 0,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        return $stmt->execute();
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
    public function obtenerProximos(int $cantidad = 10): array
    {
            $sql = "
                SELECT
                    e.*,
                    (e.cupos - COUNT(i.id)) AS cupos_libres
                FROM examenes e

                LEFT JOIN inscripciones i
                    ON i.examen_id = e.id

                WHERE
                    e.activo = 1
                    AND e.fecha >= CURDATE()

                GROUP BY e.id

                HAVING cupos_libres > 0

                ORDER BY
                    e.fecha ASC,
                    e.hora ASC

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
     * Obtiene toda la información necesaria para administrar
     * la inscripción de un alumno a un examen.
     */
    public function obtenerAdministracionInscripcion(int $id): ?array
    {
        $sql = "
            SELECT

                i.id AS inscripcion_id,
                i.usuario_id,
                i.examen_id,
                i.observaciones,

                u.nombre,
                u.apellido,
                u.dni,
                u.email,

                e.fecha,
                e.hora,
                e.ubicacion,
                e.aula,

                et.nombre AS estado,

                EXISTS(
                    SELECT 1
                    FROM documentos d
                    WHERE
                        d.usuario_id = u.id
                        AND d.tipo_documento = 'dni'
                        AND d.estado = 'aprobado'
                ) AS dni_aprobado,

                EXISTS(
                    SELECT 1
                    FROM documentos d
                    WHERE
                        d.usuario_id = u.id
                        AND d.tipo_documento = 'foto_carnet'
                        AND d.estado = 'aprobado'
                ) AS foto_aprobada,

                EXISTS(
                    SELECT 1
                    FROM inscripciones ic
                    WHERE
                        ic.usuario_id = u.id
                        AND ic.tipo_inscripcion_id = 1
                        AND ic.estado_tramite_id = 5
                ) AS curso_aprobado

            FROM inscripciones i

            INNER JOIN usuarios u
                ON u.id = i.usuario_id

            INNER JOIN examenes e
                ON e.id = i.examen_id

            LEFT JOIN estados_tramite et
                ON et.id = i.estado_tramite_id

            WHERE
                i.id = :id

            LIMIT 1
        ";

        $stmt = $this->conexion->prepare(
            $sql
        );

        $stmt->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $resultado = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $resultado ?: null;
    }
    public function obtenerInscriptos(int $examenId): array
    {
        $sql = "
            SELECT
                i.id AS inscripcion_id,
                u.id AS usuario_id,
                u.dni,
                u.apellido,
                u.nombre,
                u.email,
                i.fecha_inscripcion,
                et.nombre AS estado
            FROM inscripciones i

            INNER JOIN usuarios u
                ON u.id = i.usuario_id

            LEFT JOIN estados_tramite et
                ON et.id = i.estado_tramite_id

            WHERE
                i.examen_id = :examen_id
                AND i.tipo_inscripcion_id = 2

            ORDER BY
                u.apellido ASC,
                u.nombre ASC
        ";

        $stmt = $this->conexion->prepare(
            $sql
        );

        $stmt->bindValue(
            ':examen_id',
            $examenId,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
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
    /**
     * Actualiza el estado del trámite de una inscripción.
     */
    public function actualizarEstadoTramiteInscripcion(int $idInscripcion,int $estado): bool
    {
        $sql = "
            UPDATE inscripciones

            SET estado_tramite_id = :estado

            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare(
            $sql
        );

        $stmt->bindValue(
            ':estado',
            $estado,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':id',
            $idInscripcion,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }

    /*
    */
    public function guardarAdministracionInscripcion(int $id,int $estadoTramite,string $observaciones): bool
    {
        $this->conexion->beginTransaction();

        try {

            $this->actualizarEstadoTramite(
                $id,
                $estadoTramite
            );

            $this->actualizarObservaciones(
                $id,
                $observaciones
            );

            $this->conexion->commit();

            return true;

        } catch (Throwable $e) {

            $this->conexion->rollBack();

            throw $e;
        }
    }
    /**
    * Actualiza el estado del trámite de una inscripción.
    */
    private function actualizarEstadoTramite(int $id,int $estadoTramite): bool
    {
        $sql = "
            UPDATE inscripciones
            SET estado_tramite_id = :estado
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare(
            $sql
        );

        $stmt->bindValue(
            ':estado',
            $estadoTramite,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }
    /**
    * Actualiza las observaciones de una inscripción.
     */
    private function actualizarObservaciones(int $id,string $observaciones): bool
    {
        $sql = "
            UPDATE inscripciones
            SET observaciones = :observaciones
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare(
            $sql
        );

        $stmt->bindValue(
            ':observaciones',
            $observaciones,
            PDO::PARAM_STR
        );

        $stmt->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        return $stmt->execute();
    }
}
