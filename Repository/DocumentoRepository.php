<?php

declare(strict_types=1);


/**
 * DocumentoRepository - Repositorio del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

require_once __DIR__ . '/../db/Connection.php';

/**
 * DocumentoRepository
 *
 * Responsabilidades:
 * - Listar documentos.
 * - Obtener documento.
 * - Obtener documentos pendientes.
 * - Obtener documentos por usuario.
 * - Obtener documentos por tipo.
 * - Crear documento.
 * - Actualizar documento.
 * - Validar documento.
 * - Rechazar documento.
 * - Eliminar documento.
 * - Descargar documento.
 * - obtenerPorUsuarioYTipo
 */
class DocumentoRepository
{
    private \PDO $conexion;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }

    /**
     * Listar todos los documentos.
     */
    public function listarDocumentos(): array
    {
        $sql = "
            SELECT
                d.*,
                u.nombre,
                u.apellido,
                u.dni
            FROM documentos d
            INNER JOIN usuarios u
                ON u.id = d.usuario_id
            ORDER BY d.fecha_subida DESC
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener documento por ID.
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = "
            SELECT
                d.*,
                u.nombre,
                u.apellido,
                u.dni
            FROM documentos d
            INNER JOIN usuarios u
                ON u.id = d.usuario_id
            WHERE d.id = :id
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(
            ':id',
            $id,
            \PDO::PARAM_INT
        );

        $stmt->execute();

        $documento = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $documento ?: null;
    }

    /**
     * Obtener documentos pendientes de revisión.
     */
    public function obtenerPendientes(): array
    {
        $sql = "
            SELECT
                d.*,
                u.nombre,
                u.apellido,
                u.dni
            FROM documentos d
            INNER JOIN usuarios u
                ON u.id = d.usuario_id
            WHERE d.estado = 'pendiente'
            ORDER BY d.fecha_subida ASC
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
        /**
     * Obtener documentos de un usuario.
     */
    public function obtenerPorUsuario(int $usuarioId): array
    {
        $sql = "
            SELECT *
            FROM documentos
            WHERE usuario_id = :usuario_id
            ORDER BY fecha_subida DESC
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(
            ':usuario_id',
            $usuarioId,
            \PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener documentos por tipo.
     */
    public function obtenerPorTipo(string $tipo): array
    {
        $sql = "
            SELECT
                d.*,
                u.nombre,
                u.apellido,
                u.dni
            FROM documentos d
            INNER JOIN usuarios u
                ON u.id = d.usuario_id
            WHERE d.tipo_documento = :tipo
            ORDER BY d.fecha_subida DESC
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(
            ':tipo',
            $tipo,
            \PDO::PARAM_STR
        );

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

   
    /**
     * Crear documento.
     */
    public function crearDocumento(array $datos): int
    {
        $sql = "
            INSERT INTO documentos
            (
                usuario_id,
                tipo_documento,
                nombre_original,
                ruta_archivo,
                estado,
                fecha_subida
            )
            VALUES
            (
                :usuario_id,
                :tipo_documento,
                :nombre_original,
                :ruta_archivo,
                'pendiente',
                NOW()
            )
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':usuario_id'      => $datos['usuario_id'],
            ':tipo_documento'  => $datos['tipo_documento'],
            ':nombre_original' => $datos['nombre_original'],
            ':ruta_archivo'    => $datos['ruta_archivo']
        ]);

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Actualizar documento.
     */
    public function actualizarDocumento(
        int $id,
        array $datos
    ): bool {

        $sql = "
            UPDATE documentos
            SET
                nombre_original = :nombre_original,
                ruta_archivo = :ruta_archivo,
                estado = 'pendiente',
                observaciones = NULL,
                fecha_revision = NULL,
                fecha_subida = NOW()
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':id'               => $id,
            ':nombre_original'  => $datos['nombre_original'],
            ':ruta_archivo'     => $datos['ruta_archivo']
        ]);
    }
        /**
     * Aprobar un documento.
     */
    public function validarDocumento(
        int $id,
        string $observaciones = ''
    ): bool {

        $sql = "
            UPDATE documentos
            SET
                estado = 'aprobado',
                observaciones = :observaciones,
                fecha_revision = NOW()
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':observaciones' => $observaciones
        ]);
    }

    /**
     * Rechazar un documento.
     */
    public function rechazarDocumento(
        int $id,
        string $observaciones = ''
    ): bool {

        $sql = "
            UPDATE documentos
            SET
                estado = 'rechazado',
                observaciones = :observaciones,
                fecha_revision = NOW()
            WHERE id = :id
        ";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':observaciones' => $observaciones
        ]);
    }

    /**
     * Eliminar un documento.
     */
    public function eliminarDocumento(int $id): bool
    {
        $stmt = $this->conexion->prepare("
            DELETE
            FROM documentos
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id
        ]);
    }

    /**
     * Obtener la información necesaria para descargar un documento.
     */
    public function descargarDocumento(int $id): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT
                id,
                nombre_original,
                ruta_archivo,
                tipo_documento,
                usuario_id
            FROM documentos
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $documento = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $documento ?: null;
    }

        /**
     * Obtener un documento por usuario y tipo.
     */
    public function obtenerPorUsuarioYTipo(int $usuarioId,string $tipoDocumento): ?array 
    {

        $sql = "
            SELECT *
            FROM documentos
            WHERE usuario_id = :usuario_id
            AND tipo_documento = :tipo_documento
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':tipo_documento' => $tipoDocumento
        ]);

        $documento = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $documento ?: null;
    }

   public function obtenerFotoCarnet(int $usuarioId): ?array
    {
        $sql = "
            SELECT
                id,
                usuario_id,
                ruta_archivo,
                nombre_original,
                tipo_mime
            FROM documentos
            WHERE usuario_id = :usuario_id
            AND tipo_documento = 'foto_carnet'
            AND estado = 'aprobado'
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':usuario_id' => $usuarioId
        ]);

        $documento = $stmt->fetch(PDO::FETCH_ASSOC);

        return $documento ?: null;
    }
}
