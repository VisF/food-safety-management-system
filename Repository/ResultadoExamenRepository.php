<?php
declare(strict_types=1);


/**
 * ResultadoExamenRepository - Repositorio del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * Métodos:
 * - crear()
 * - obtenerPorId()
 * - obtenerPorInscripcion()
 * - obtenerPorExamen()
 * - obtenerPorUsuario()
 * - listarResultados()
 * - actualizar()
 * - eliminar()
 * - contarAprobados()
 * - contarReprobados()
 * - obtenerPromedioExamen()
 */

require_once __DIR__ . '/../db/Connection.php';

class ResultadoExamenRepository
{
    private \PDO $conexion;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }

    /**
     * Crear resultado.
     */
    public function crear(array $datos): ?int
    {
        $sql = "
            INSERT INTO resultado_examen
            (
                inscripcion_id,
                examen_id,
                nota,
                aprobado,
                fecha_resultado,
                observaciones
            )
            VALUES
            (
                :inscripcion,
                :examen,
                :nota,
                :aprobado,
                NOW(),
                :observaciones
            )
        ";

        $stmt = $this->conexion->prepare($sql);

        $ok = $stmt->execute([
            ':inscripcion' => $datos['inscripcion_id'],
            ':examen' => $datos['id_examen'],
            ':nota' => $datos['nota'],
            ':aprobado' => $datos['aprobado'],
            ':observaciones' => $datos['observaciones'] ?? null
        ]);

        if (!$ok) {
            return null;
        }

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Obtener resultado por ID.
     */
    public function obtenerPorId(
        int $id
    ): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM resultado_examen
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Obtener resultado por inscripción.
     */
    public function obtenerPorInscripcion(
        int $inscripcionId
    ): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM resultado_examen
            WHERE inscripcion_id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $inscripcionId
        ]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

        /**
     * Obtener resultados de un examen.
     */
    public function obtenerPorExamen(
        int $examenId
    ): array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM resultado_examen
            WHERE examen_id = :id
            ORDER BY fecha_resultado DESC
        ");

        $stmt->execute([
            ':id' => $examenId
        ]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener resultados de un usuario.
     */
    public function obtenerPorUsuario(
        int $usuarioId
    ): array
    {
        $stmt = $this->conexion->prepare("
            SELECT re.*

            FROM resultado_examen re

            INNER JOIN inscripciones i
                ON i.id = re.inscripcion_id

            WHERE i.usuario_id = :usuario

            ORDER BY re.fecha_resultado DESC
        ");

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Listar todos los resultados.
     */
    public function listarResultados(): array
    {
        $stmt = $this->conexion->query("
            SELECT *
            FROM resultado_examen
            ORDER BY fecha_resultado DESC
        ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Actualizar resultado.
     */
    public function actualizar(
        int $id,
        array $datos
    ): bool
    {
        $sql = "
            UPDATE resultado_examen
            SET
                nota = :nota,
                aprobado = :aprobado,
                observaciones = :observaciones
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':nota' => $datos['nota'],
            ':aprobado' => $datos['aprobado'],
            ':observaciones' =>
                $datos['observaciones'] ?? null
        ]);
    }
        /**
     * Eliminar resultado.
     */
    public function eliminar(
        int $id
    ): bool
    {
        $stmt = $this->conexion->prepare("
            DELETE
            FROM resultado_examen
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id
        ]);
    }

    /**
     * Contar aprobados de un examen.
     */
    public function contarAprobados(
        int $examenId
    ): int
    {
        $stmt = $this->conexion->prepare("
            SELECT COUNT(*)

            FROM resultado_examen

            WHERE examen_id = :id
            AND aprobado = 1
        ");

        $stmt->execute([
            ':id' => $examenId
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Contar reprobados de un examen.
     */
    public function contarReprobados(
        int $examenId
    ): int
    {
        $stmt = $this->conexion->prepare("
            SELECT COUNT(*)

            FROM resultado_examen

            WHERE examen_id = :id
            AND aprobado = 0
        ");

        $stmt->execute([
            ':id' => $examenId
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Obtener promedio de un examen.
     */
    public function obtenerPromedioExamen(
        int $examenId
    ): float
    {
        $stmt = $this->conexion->prepare("
            SELECT AVG(nota)

            FROM resultado_examen

            WHERE examen_id = :id
        ");

        $stmt->execute([
            ':id' => $examenId
        ]);

        return (float)$stmt->fetchColumn();
    }
}
