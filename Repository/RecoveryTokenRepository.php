<?php
declare(strict_types=1);

require_once __DIR__ . '/../db/Connection.php';

/**
 * RecoveryTokenRepository
 *
 * Gestiona exclusivamente el acceso a la tabla recovery_tokens.
 */
class RecoveryTokenRepository
{
    private PDO $conexion;

    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }

    /**
     * Guarda el hash de un token de recuperación.
     */
    public function crear(
        int $usuarioId,
        string $tokenHash,
        string $expiracion
    ): int {
        $sql = "
            INSERT INTO recovery_tokens (
                usuario_id,
                token,
                expiracion,
                usado
            ) VALUES (
                :usuario_id,
                :token,
                :expiracion,
                0
            )
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':token' => $tokenHash,
            ':expiracion' => $expiracion,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    /**
     * Busca un token por su hash, siempre que esté vigente
     * y todavía no haya sido utilizado.
     */
    public function obtenerValidoPorHash(string $tokenHash): ?array
    {
        $sql = "
            SELECT
                id,
                usuario_id,
                expiracion,
                fecha_creacion
            FROM recovery_tokens
            WHERE token = :token
            AND usado = 0
            AND expiracion > NOW()
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            ':token' => $tokenHash,
        ]);

        $token = $stmt->fetch(PDO::FETCH_ASSOC);

        return $token ?: null;
    }

    /**
     * Marca un token como utilizado.
     *
     * Solo lo hace si continúa vigente y sin utilizar.
     */
    public function marcarComoUsado(int $tokenId): bool
    {
        $sql = "
            UPDATE recovery_tokens
            SET usado = 1
            WHERE id = :id
              AND usado = 0
              AND expiracion > NOW()
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':id' => $tokenId,
        ]);

        return $stmt->rowCount() === 1;
    }

    /**
     * Invalida los tokens pendientes de un usuario.
     */
    public function invalidarPorUsuario(int $usuarioId): bool
    {
        $sql = "
            UPDATE recovery_tokens
            SET usado = 1
            WHERE usuario_id = :usuario_id
              AND usado = 0
        ";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':usuario_id' => $usuarioId,
        ]);
    }

    /**
     * Elimina tokens antiguos para mantener limpia la tabla.
     *
     * @param int $diasAntiguedad Antigüedad mínima en días.
     */
    public function eliminarAntiguos(int $diasAntiguedad = 30): int
    {
        if ($diasAntiguedad < 1) {
            throw new InvalidArgumentException(
                'La antigüedad debe ser de al menos un día.'
            );
        }

        $sql = "
            DELETE FROM recovery_tokens
            WHERE fecha_creacion < DATE_SUB(
                NOW(),
                INTERVAL :dias DAY
            )
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bindValue(
            ':dias',
            $diasAntiguedad,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->rowCount();
    }
    
    /**
     * Consulta la fecha de creación de un token.
     */
    public function consultarFechaDeCreacion(int $tokenId): ?string
    {
        $sql = "
            SELECT fecha_creacion
            FROM recovery_tokens
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':id' => $tokenId,
        ]);

        $fecha = $stmt->fetchColumn();

        return $fecha !== false ? (string) $fecha : null;
    }

}
