<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/Connection.php';

class NotificacionRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    // =====================================================
    // CONSULTAS
    // =====================================================

    /**
     * Obtiene una notificación por ID.
     */
    public function obtenerPorId(
        int $id
    ): ?array
    {
        $sql = "
            SELECT *
            FROM notificaciones
            WHERE id = :id
            LIMIT 1
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return
            $stmt->fetch(PDO::FETCH_ASSOC)
            ?: null;
    }

    /**
     * Obtiene las notificaciones pendientes
     * de un usuario.
     */
    public function obtenerPendientes(
        int $usuarioId
    ): array
    {
        $sql = "
            SELECT *
            FROM notificaciones
            WHERE usuario_id = :usuario
              AND enviado = 0
            ORDER BY fecha_creacion DESC
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return
            $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene el historial de un usuario.
     */
    public function obtenerHistorial(
        int $usuarioId
    ): array
    {
        $sql = "
            SELECT *
            FROM notificaciones
            WHERE usuario_id = :usuario
            ORDER BY fecha_creacion DESC
            LIMIT 100
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return
            $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene notificaciones por tipo.
     */
    public function obtenerPorTipo(
        string $tipo
    ): array
    {
        $sql = "
            SELECT *
            FROM notificaciones
            WHERE tipo = :tipo
            ORDER BY fecha_creacion DESC
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':tipo' => $tipo
        ]);

        return
            $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =====================================================
    // MODIFICACIONES
    // =====================================================

    /**
     * Crea una notificación.
     */
    public function crear(
        array $datos
    ): int
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
                :usuario_id,
                :tipo,
                :asunto,
                :mensaje,
                0,
                NOW()
            )
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':usuario_id' =>
                $datos['usuario_id'],
            ':tipo' =>
                $datos['tipo'],
            ':asunto' =>
                $datos['asunto'] ?? '',
            ':mensaje' =>
                $datos['mensaje'] ?? ''
        ]);

        return
            (int)$this->db->lastInsertId();
    }

    /**
     * Marca una notificación como enviada.
     */
    public function marcarEnviada(
        int $id
    ): bool
    {
        $sql = "
            UPDATE notificaciones
            SET
                enviado = 1,
                fecha_envio = NOW()
            WHERE id = :id
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return
            $stmt->rowCount() > 0;
    }

    /**
     * Incrementa los intentos de envío.
     */
    public function incrementarIntentos(
        int $id,
        string $error
    ): bool
    {
        $sql = "
            UPDATE notificaciones
            SET
                attempts = attempts + 1,
                last_error = :error
            WHERE id = :id
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id,
            ':error' => $error
        ]);

        return
            $stmt->rowCount() > 0;
    }

    /**
     * Elimina una notificación.
     */
    public function eliminar(
        int $id
    ): bool
    {
        $sql = "
            DELETE
            FROM notificaciones
            WHERE id = :id
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return
            $stmt->rowCount() > 0;
    }

    /**
     * Obtiene la cola de notificaciones pendientes.
     */
    public function obtenerColaPendiente(
        int $limite = 100
    ): array
    {
        $sql = "
            SELECT *
            FROM notificaciones
            WHERE enviado = 0
            ORDER BY fecha_creacion ASC
            LIMIT :limite
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->bindValue(
            ':limite',
            $limite,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return
            $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

        // =====================================================
    // ESTADÍSTICAS
    // =====================================================

    /**
     * Cuenta las notificaciones pendientes.
     */
    public function contarPendientes(): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM notificaciones
            WHERE enviado = 0
        ";

        $stmt =
            $this->db->query($sql);

        return (int)
            $stmt->fetch(
                PDO::FETCH_ASSOC
            )['total'];
    }

    /**
     * Cuenta las notificaciones enviadas.
     */
    public function contarEnviadas(): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM notificaciones
            WHERE enviado = 1
        ";

        $stmt =
            $this->db->query($sql);

        return (int)
            $stmt->fetch(
                PDO::FETCH_ASSOC
            )['total'];
    }

    /**
     * Cuenta las notificaciones por tipo.
     */
    public function contarPorTipo(
        string $tipo
    ): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM notificaciones
            WHERE tipo = :tipo
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':tipo' => $tipo
        ]);

        return (int)
            $stmt->fetch(
                PDO::FETCH_ASSOC
            )['total'];
    }

    /**
     * Obtiene la última notificación enviada
     * a un usuario.
     */
    public function obtenerUltimaPorUsuario(
        int $usuarioId
    ): ?array
    {
        $sql = "
            SELECT *
            FROM notificaciones
            WHERE usuario_id = :usuario
            ORDER BY fecha_creacion DESC
            LIMIT 1
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return
            $stmt->fetch(
                PDO::FETCH_ASSOC
            ) ?: null;
    }

    /**
     * Verifica si existe una notificación.
     */
    public function existe(
        int $id
    ): bool
    {
        $sql = "
            SELECT 1
            FROM notificaciones
            WHERE id = :id
            LIMIT 1
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return
            (bool)
            $stmt->fetchColumn();
    }
        // =====================================================
    // UTILIDADES
    // =====================================================

    /**
     * Obtiene la cantidad de notificaciones
     * de un usuario.
     */
    public function contarPorUsuario(
        int $usuarioId
    ): int
    {
        $sql = "
            SELECT COUNT(*) AS total
            FROM notificaciones
            WHERE usuario_id = :usuario
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return (int)
            $stmt->fetch(
                PDO::FETCH_ASSOC
            )['total'];
    }

    /**
     * Obtiene todas las notificaciones
     * de un usuario.
     */
    public function obtenerPorUsuario(
        int $usuarioId
    ): array
    {
        $sql = "
            SELECT *
            FROM notificaciones
            WHERE usuario_id = :usuario
            ORDER BY fecha_creacion DESC
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return
            $stmt->fetchAll(
                PDO::FETCH_ASSOC
            );
    }

    /**
     * Elimina todas las notificaciones
     * de un usuario.
     */
    public function eliminarPorUsuario(
        int $usuarioId
    ): bool
    {
        $sql = "
            DELETE
            FROM notificaciones
            WHERE usuario_id = :usuario
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return
            $stmt->rowCount() > 0;
    }

    /**
     * Marca todas las notificaciones
     * de un usuario como enviadas.
     */
    public function marcarTodasEnviadas(
        int $usuarioId
    ): bool
    {
        $sql = "
            UPDATE notificaciones
            SET
                enviado = 1,
                fecha_envio = NOW()
            WHERE usuario_id = :usuario
              AND enviado = 0
        ";

        $stmt =
            $this->db->prepare($sql);

        $stmt->execute([
            ':usuario' => $usuarioId
        ]);

        return
            $stmt->rowCount() > 0;
    }
}