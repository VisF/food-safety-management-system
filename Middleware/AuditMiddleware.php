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
                    id_usuario,
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
                    $_SESSION['usuario_id'] ?? 0,

                'tabla' =>
                    $tabla,

                'accion' =>
                    $accion,

                'anterior' =>
                    json_encode($anterior),

                'nuevo' =>
                    json_encode($nuevo),

                'ip' =>
                    $_SERVER['REMOTE_ADDR'] ?? '',

                'ua' =>
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);

        } catch (\Throwable $e) {

            error_log(
                'Audit Error: ' .
                $e->getMessage()
            );
        }
    }
}