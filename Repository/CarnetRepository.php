<?php
declare(strict_types=1);


/**
 * CarnetRepository - Repositorio del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * Métodos:
 * - crear()
 * - obtenerPorInscripcion()
 * - obtenerPorDNI()
 * - verificarVigencia()
 * - actualizar()
 * - obtenerCarnetsVencidos()
 * - renovar()
 */

require_once __DIR__ . '/../db/Connection.php';

class CarnetRepository
{
    private \PDO $conexion;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }

    /**
     * Crear un carnet.
     */
    public function crear(array $datos): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT id
            FROM carnets
            WHERE numero_carnet = :numero
        ");

        $stmt->execute([
            ':numero' => $datos['numero_carnet']
        ]);

        if ($stmt->fetch()) {
            return null;
        }

        $sql = "
            INSERT INTO carnets
            (
                inscripcion_id,
                numero_carnet,
                fecha_emision,
                fecha_vencimiento,
                activo
            )
            VALUES
            (
                :inscripcion,
                :numero,
                :emision,
                :vencimiento,
                1
            )
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':inscripcion' => $datos['id_inscripcion'],
            ':numero' => $datos['numero_carnet'],
            ':emision' => $datos['fecha_emision'],
            ':vencimiento' => $datos['fecha_vencimiento']
        ]);

        return [
            'id' => (int)$this->conexion->lastInsertId(),
            'id_inscripcion' => $datos['id_inscripcion'],
            'numero_carnet' => $datos['numero_carnet']
        ];
    }

    /**
     * Obtener carnet por inscripción.
     */
    public function obtenerPorInscripcion(
        int $idInscripcion
    ): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM carnets
            WHERE inscripcion_id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $idInscripcion
        ]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Obtener carnet por DNI.
     */
    public function obtenerPorDNI(
        string $dni
    ): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT c.*

            FROM carnets c

            INNER JOIN inscripciones i
                ON c.inscripcion_id = i.id

            INNER JOIN usuarios u
                ON u.id = i.usuario_id

            WHERE u.dni = :dni

            LIMIT 1
        ");

        $stmt->execute([
            ':dni' => $dni
        ]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }
        /**
     * Verificar si un carnet está vigente.
     */
    public function verificarVigencia(
        int $id
    ): bool
    {
        $stmt = $this->conexion->prepare("
            SELECT
                fecha_vencimiento,
                activo
            FROM carnets
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        if ((int)$row['activo'] !== 1) {
            return false;
        }

        return
            strtotime($row['fecha_vencimiento'])
            >
            time();
    }

    /**
     * Actualizar un carnet.
     */
    public function actualizar(
        int $id,
        array $datos
    ): bool
    {
        $sql = "
            UPDATE carnets
            SET
                numero_carnet = :numero,
                fecha_emision = :emision,
                fecha_vencimiento = :vencimiento,
                activo = :activo
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':numero' => $datos['numero_carnet'],
            ':emision' => $datos['fecha_emision'],
            ':vencimiento' => $datos['fecha_vencimiento'],
            ':activo' => (int)$datos['activo']
        ]);
    }

    /**
     * Obtener carnets vencidos.
     */
    public function obtenerCarnetsVencidos(): array
    {
        $stmt = $this->conexion->query("
            SELECT *
            FROM carnets
            WHERE
                activo = 1
                AND fecha_vencimiento < CURDATE()
            ORDER BY fecha_vencimiento ASC
        ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Renovar un carnet.
     */
    public function renovar(
        int $id,
        string $fechaVencimiento
    ): bool
    {
        $stmt = $this->conexion->prepare("
            UPDATE carnets
            SET
                fecha_emision = CURDATE(),
                fecha_vencimiento = :vencimiento,
                activo = 1
            WHERE id = :id
        ");

        return $stmt->execute([
            ':vencimiento' => $fechaVencimiento,
            ':id' => $id
        ]);
    }
}
