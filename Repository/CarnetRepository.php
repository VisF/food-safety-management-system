<?php
declare(strict_types=1);

/**
 * CarnetRepository
 *
 * Encapsula todas las operaciones de persistencia
 * relacionadas con los carnets.
 */

require_once __DIR__ . '/../db/Connection.php';

class CarnetRepository
{
    private PDO $conexion;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }

    /**
     * Crea un nuevo carnet.
     *
     * @param array $datos
     * @return array|null
     */
    public function crear(array $datos): ?array
    {
        // Verificar que el número no exista.
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
            ':numero'      => $datos['numero_carnet'],
            ':emision'     => $datos['fecha_emision'],
            ':vencimiento' => $datos['fecha_vencimiento']
        ]);

        return [
            'id'               => (int) $this->conexion->lastInsertId(),
            'id_inscripcion'   => $datos['id_inscripcion'],
            'numero_carnet'    => $datos['numero_carnet'],
            'fecha_emision'    => $datos['fecha_emision'],
            'fecha_vencimiento'=> $datos['fecha_vencimiento'],
            'activo'           => 1
        ];
    }

        /**
     * Obtiene un carnet por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = "
            SELECT *
            FROM carnets
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Obtiene un carnet por el ID de la inscripción.
     *
     * @param int $idInscripcion
     * @return array|null
     */
    public function obtenerPorInscripcionId(int $idInscripcion): ?array
    {
        $sql = "
            SELECT *
            FROM carnets
            WHERE inscripcion_id = :id
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':id' => $idInscripcion
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Obtiene un carnet por su número.
     *
     * @param string $numeroCarnet
     * @return array|null
     */
    public function obtenerPorNumero(string $numeroCarnet): ?array
    {
        $sql = "
            SELECT *
            FROM carnets
            WHERE numero_carnet = :numero
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':numero' => $numeroCarnet
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }

    /**
     * Obtiene un carnet asociado a un DNI.
     *
     * @param string $dni
     * @return array|null
     */
    public function obtenerPorDni(string $dni): ?array
    {
        $sql = "
            SELECT c.*
            FROM carnets c
            INNER JOIN inscripciones i
                ON c.inscripcion_id = i.id
            INNER JOIN usuarios u
                ON u.id = i.usuario_id
            WHERE u.dni = :dni
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':dni' => $dni
        ]);

        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

        return $resultado ?: null;
    }
        /**
     * Actualiza un carnet.
     *
     * @param int $id
     * @param array $datos
     * @return bool
     */
    public function actualizar(int $id, array $datos): bool
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
            ':id'           => $id,
            ':numero'       => $datos['numero_carnet'],
            ':emision'      => $datos['fecha_emision'],
            ':vencimiento'  => $datos['fecha_vencimiento'],
            ':activo'       => (int) $datos['activo']
        ]);
    }

    /**
     * Anula un carnet.
     *
     * @param int $idCarnet
     * @return bool
     */
    public function anular(int $idCarnet): bool
    {
        $sql = "
            UPDATE carnets
            SET activo = 0
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':id' => $idCarnet
        ]);
    }

    /**
     * Renueva un carnet.
     *
     * @param int $idCarnet
     * @param string $fechaVencimiento
     * @return bool
     */
    public function renovar(
        int $idCarnet,
        string $fechaVencimiento
    ): bool
    {
        $sql = "
            UPDATE carnets
            SET
                fecha_emision = CURDATE(),
                fecha_vencimiento = :vencimiento,
                activo = 1
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':id'          => $idCarnet,
            ':vencimiento' => $fechaVencimiento
        ]);
    }
        /**
     * Lista todos los carnets activos.
     *
     * @return array
     */
    public function listarActivos(): array
    {
        $sql = "
            SELECT *
            FROM carnets
            WHERE activo = 1
            ORDER BY fecha_emision DESC
        ";

        $stmt = $this->conexion->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene todos los carnets vencidos.
     *
     * @return array
     */
    public function obtenerCarnetsVencidos(): array
    {
        $sql = "
            SELECT *
            FROM carnets
            WHERE activo = 1
              AND fecha_vencimiento < CURDATE()
            ORDER BY fecha_vencimiento ASC
        ";

        $stmt = $this->conexion->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Obtiene el último carnet emitido para un usuario.
     */
    public function obtenerPorUsuarioId(
        int $usuarioId
    ): ?array
    {
        $sql = "
            SELECT c.*

            FROM carnets c

            INNER JOIN inscripciones i
                ON i.id = c.inscripcion_id

            WHERE i.usuario_id = :usuario

            ORDER BY c.fecha_emision DESC

            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        $carnet = $stmt->fetch(PDO::FETCH_ASSOC);

        return $carnet ?: null;
    }
    /**
     * Obtiene la ruta del PDF del carnet.
     */
    public function obtenerPdfPorDni(
        string $dni
    ): ?string
    {
        $sql = "
            SELECT c.ruta_pdf

            FROM carnets c

            INNER JOIN inscripciones i
                ON i.id = c.inscripcion_id

            INNER JOIN usuarios u
                ON u.id = i.usuario_id

            WHERE u.dni = :dni

            ORDER BY c.fecha_emision DESC

            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':dni' => $dni
        ]);

        $ruta = $stmt->fetchColumn();

        return $ruta ?: null;
    }
    /**
     * Obtener el último carnet de un usuario.
     *
     * @param int $usuarioId
     * @return array|null
     */
    public function obtenerUltimoCarnetUsuario(
        int $usuarioId
    ): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT
                c.*

            FROM carnets c

            INNER JOIN inscripciones i
                ON i.id = c.inscripcion_id

            WHERE
                i.usuario_id = :usuario

            ORDER BY
                c.fecha_vencimiento DESC

            LIMIT 1
        ");

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        $carnet =
            $stmt->fetch(\PDO::FETCH_ASSOC);

        return $carnet ?: null;
    }
    public function obtenerCarnetPublicoPorDni(string $dni): ?array
    {
        $sql = "
            SELECT
                c.id AS id_carnet,
                c.numero_carnet,
                c.fecha_emision,
                c.fecha_vencimiento,
                c.ruta_pdf,
                c.vigente,

                i.usuario_id,

                u.nombre,
                u.apellido,
                u.dni

            FROM carnets c

            INNER JOIN inscripciones i
                ON c.inscripcion_id = i.id

            INNER JOIN usuarios u
                ON i.usuario_id = u.id

            WHERE u.dni = :dni

            LIMIT 1

            ORDER BY c.fecha_emision DESC
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':dni' => $dni
        ]);

        $carnet = $stmt->fetch(PDO::FETCH_ASSOC);

        return $carnet ?: null;
    }
    public function obtenerPorIdConsultaPublica(int $idCarnet): ?array
    {
        $sql = "
            SELECT
                c.id,
                c.inscripcion_id,
                c.numero_carnet,
                c.fecha_emision,
                c.fecha_vencimiento,
                c.ruta_pdf,
                c.vigente,

                i.usuario_id

            FROM carnets c

            INNER JOIN inscripciones i
                ON c.inscripcion_id = i.id

            WHERE c.id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':id' => $idCarnet
        ]);

        $carnet = $stmt->fetch(PDO::FETCH_ASSOC);

        return $carnet ?: null;
    }
}