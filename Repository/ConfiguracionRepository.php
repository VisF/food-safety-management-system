<?php

declare(strict_types=1);

require_once __DIR__ . '/../db/Connection.php';

class ConfiguracionRepository
{
    private \PDO $conexion;

    public function __construct()
    {
        $this->conexion = Connection::getPDO();
    }

    /**
     * Obtiene una configuración por su clave.
     */
    public function obtenerPorClave(string $clave): ?array
    {
        $sql = "
            SELECT
                id,
                clave,
                valor,
                descripcion
            FROM configuracion_sistema
            WHERE clave = :clave
            LIMIT 1
        ";

        $stmt = $this->conexion->prepare($sql);

        $stmt->execute([
            ':clave' => $clave
        ]);

        $configuracion = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $configuracion ?: null;
    }

    /**
     * Obtiene el valor de una configuración por su clave.
     */
    public function obtenerValor(string $clave): ?string
    {
        $configuracion = $this->obtenerPorClave($clave);

        if ($configuracion === null) {
            return null;
        }

        return (string)$configuracion['valor'];
    }

    /**
     * Actualiza el valor de una configuración.
     */
    public function actualizarValor(
        string $clave,
        string $valor
    ): bool {
        $sql = "
            UPDATE configuracion_sistema
            SET valor = :valor
            WHERE clave = :clave
        ";

        $stmt = $this->conexion->prepare($sql);

        return $stmt->execute([
            ':valor' => $valor,
            ':clave' => $clave
        ]);
    }
}