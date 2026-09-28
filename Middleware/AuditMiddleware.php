<?php

namespace App\Middleware;

use App\Config\Database;
use PDO;

class AuditMiddleware
{
    public static function log(
        string $accion,
        string $tabla,
        array $anterior = [],
        array $nuevo = []
    ): void {
        try {
            $pdo = Database::getConnection();

            $sql = "
                INSERT INTO auditoria_acciones
                (
                    usuario_id,
                    tabla_afectada,
                    accion,
                    datos_anteriores,
                    datos_nuevos,
                    ip,
                    user_agent
                )
                VALUES
                (
                    :usuario,
                    :tabla,
                    :accion,
                    :anterior,
                    :nuevo,
                    :ip,
                    :ua
                )
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'usuario' =>
                    $_SESSION['usuario_id'] ?? null,

                'tabla' =>
                    $tabla,

                'accion' =>
                    $accion,

                'anterior' =>
                    json_encode(
                        $anterior,
                        JSON_UNESCAPED_UNICODE
                    ),

                'nuevo' =>
                    json_encode(
                        $nuevo,
                        JSON_UNESCAPED_UNICODE
                    ),

                'ip' =>
                    $_SERVER['REMOTE_ADDR'] ?? null,

                'ua' =>
                    $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

        } catch (\Throwable $e) {
            error_log(
                'Audit Error: ' .
                $e->getMessage()
            );
        }
    }
}
