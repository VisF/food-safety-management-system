<?php
declare(strict_types=1);


/**
 * NotificacionRepository - Repositorio del sistema.
 *
 * Define la l?gica principal del m?dulo y sus operaciones p?blicas.
 */

/**
 * Repositorio para gestionar notificaciones.
 *
 * Métodos:
 * - crear()
 * - obtenerPorId()
 * - obtenerPendientes()
 * - obtenerPorUsuario()
 * - obtenerPorTipo()
 * - marcarEnviada()
 * - eliminar()
 * - obtenerNoEnviadas()
 * - obtenerPorEstado()
 * - obtenerUltimas()
 * - obtenerUsuarioDestino()
 * - obtenerInscripcion()
 * - obtenerDocumento()
 * - obtenerResultadoExamen()
 * - obtenerCarnet()
 * - guardarRecoveryToken()
 * - obtenerCurso()
 * - contarDocumentos()
 * - contarDocumentosAprobados()
 */

require_once __DIR__ . '/../db/Connection.php';

class NotificacionRepository
{
    private PDO $conexion;

    // Inicializa las dependencias de la clase.
    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }

    /**
     * Crear una notificación.
     */
    public function crear(array $datos): int
    {
        $sql = "
            INSERT INTO notificaciones
            (
                usuario_id,
                tipo,
                asunto,
                mensaje,
                enviado,
                fecha_creacion
            )
            VALUES
            (
                :usuario,
                :tipo,
                :asunto,
                :mensaje,
                0,
                NOW()
            )
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':usuario' => $datos['usuario_id'],
            ':tipo' => $datos['tipo'],
            ':asunto' => $datos['asunto'],
            ':mensaje' => $datos['mensaje']
        ]);

        return (int)$this->conexion->lastInsertId();
    }

    /**
     * Obtener una notificación por ID.
     */
    public function obtenerPorId(
        int $id
    ): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM notificaciones
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $id
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ?: null;
    }

    /**
     * Obtener notificaciones pendientes.
     */
    public function obtenerPendientes(): array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM notificaciones
            WHERE enviado = 0
            ORDER BY fecha_creacion ASC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener notificaciones de un usuario.
     */
    public function obtenerPorUsuario(
        int $usuarioId
    ): array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM notificaciones
            WHERE usuario_id = :usuario
            ORDER BY fecha_creacion DESC
        ");

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener notificaciones por tipo.
     */
    public function obtenerPorTipo(
        string $tipo
    ): array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM notificaciones
            WHERE tipo = :tipo
            ORDER BY fecha_creacion DESC
        ");

        $stmt->execute([
            ':tipo' => $tipo
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
        /**
     * Marcar una notificación como enviada.
     */
    public function marcarEnviada(
        int $id
    ): bool
    {
        $stmt = $this->conexion->prepare("
            UPDATE notificaciones
            SET
                enviado = 1,
                fecha_envio = NOW()
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id
        ]);
    }

    /**
     * Eliminar una notificación.
     */
    public function eliminar(
        int $id
    ): bool
    {
        $stmt = $this->conexion->prepare("
            DELETE
            FROM notificaciones
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id
        ]);
    }

    /**
     * Obtener notificaciones no enviadas.
     */
    public function obtenerNoEnviadas(): array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM notificaciones
            WHERE enviado = 0
            ORDER BY fecha_creacion ASC
        ");

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener notificaciones por estado.
     */
    public function obtenerPorEstado(
        bool $enviado
    ): array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM notificaciones
            WHERE enviado = :enviado
            ORDER BY fecha_creacion DESC
        ");

        $stmt->execute([
            ':enviado' => $enviado ? 1 : 0
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener las últimas notificaciones.
     */
    public function obtenerUltimas(
        int $cantidad = 20
    ): array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM notificaciones
            ORDER BY fecha_creacion DESC
            LIMIT :cantidad
        ");

        $stmt->bindValue(
            ':cantidad',
            $cantidad,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
        /**
     * Obtener usuario destino.
     */
    public function obtenerUsuarioDestino(
        int $usuarioId
    ): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT
                id,
                nombre,
                apellido,
                email,
                dni
            FROM usuarios
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $usuarioId
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ?: null;
    }

    /**
     * Obtener una inscripción.
     */
    public function obtenerInscripcion(
        int $inscripcionId
    ): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM inscripciones
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $inscripcionId
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ?: null;
    }

    /**
     * Obtener un documento.
     */
    public function obtenerDocumento(
        int $documentoId
    ): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM documentos
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $documentoId
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ?: null;
    }

    /**
     * Obtener un resultado de examen.
     */
    public function obtenerResultadoExamen(
        int $resultadoId
    ): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM resultado_examen
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $resultadoId
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ?: null;
    }

    /**
     * Obtener un carnet.
     */
    public function obtenerCarnet(
        int $carnetId
    ): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM carnets
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $carnetId
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ?: null;
    }
        /**
     * Guardar token de recuperación de contraseña.
     */
    public function guardarRecoveryToken(
        int $usuarioId,
        string $token,
        string $expira
    ): bool
    {
        $stmt = $this->conexion->prepare("
            INSERT INTO recovery_tokens
            (
                usuario_id,
                token,
                fecha_expiracion
            )
            VALUES
            (
                :usuario,
                :token,
                :expira
            )
        ");

        return $stmt->execute([
            ':usuario' => $usuarioId,
            ':token' => $token,
            ':expira' => $expira
        ]);
    }

    /**
     * Obtener un curso.
     */
    public function obtenerCurso(
        int $cursoId
    ): ?array
    {
        $stmt = $this->conexion->prepare("
            SELECT *
            FROM cursos
            WHERE id = :id
        ");

        $stmt->execute([
            ':id' => $cursoId
        ]);

        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ?: null;
    }

    /**
     * Contar documentos de un usuario.
     */
    public function contarDocumentos(
        int $usuarioId
    ): int
    {
        $stmt = $this->conexion->prepare("
            SELECT COUNT(*)
            FROM documentos
            WHERE usuario_id = :usuario
        ");

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Contar documentos aprobados.
     */
    public function contarDocumentosAprobados(
        int $usuarioId
    ): int
    {
        $stmt = $this->conexion->prepare("
            SELECT COUNT(*)
            FROM documentos
            WHERE usuario_id = :usuario
            AND estado = 'aprobado'
        ");

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return (int)$stmt->fetchColumn();
    }
}
